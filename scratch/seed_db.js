const fs = require('fs');
const path = require('path');
const admin = require('firebase-admin');

// IMPORTANT: Download your Service Account JSON Key from Firebase Console:
// Project Settings -> Service Accounts -> Generate New Private Key
// Save it in this directory as 'serviceAccountKey.json'
const serviceAccountPath = path.join(__dirname, 'serviceAccountKey.json');

if (!fs.existsSync(serviceAccountPath)) {
  console.error("==========================================================");
  console.error("ERROR: 'serviceAccountKey.json' not found!");
  console.error("Please go to Firebase Console -> Project Settings -> Service Accounts");
  console.error("Click 'Generate New Private Key' and save it as:");
  console.error(serviceAccountPath);
  console.error("==========================================================");
  process.exit(1);
}

const serviceAccount = require(serviceAccountPath);

admin.initializeApp({
  credential: admin.credential.cert(serviceAccount)
});

const db = admin.firestore();

// Path to your restaurantDB.txt file
const sqlFilePath = path.join(__dirname, '..', 'restaurantDB.txt');

if (!fs.existsSync(sqlFilePath)) {
  console.error(`ERROR: SQL file not found at ${sqlFilePath}`);
  process.exit(1);
}

const sqlContent = fs.readFileSync(sqlFilePath, 'utf8');

// Regex helper to extract insert values
function parseInserts(tableName, sql) {
  const regex = new RegExp(`INSERT INTO \`${tableName}\` [^)]*\\) VALUES\\s*([\\s\\S]*?);`, 'i');
  const match = sql.match(regex);
  if (!match) return [];
  
  // Split matches into individual value rows, ignoring commas between rows
  const valuesText = match[1].trim();
  const rows = [];
  let currentRow = "";
  let insideString = false;
  let escape = false;
  let depth = 0;

  for (let i = 0; i < valuesText.length; i++) {
    const char = valuesText[i];
    
    if (escape) {
      currentRow += char;
      escape = false;
      continue;
    }
    if (char === '\\') {
      currentRow += char;
      escape = true;
      continue;
    }
    if (char === "'") {
      insideString = !insideString;
      currentRow += char;
      continue;
    }
    
    if (!insideString) {
      if (char === '(') {
        depth++;
      } else if (char === ')') {
        depth--;
        if (depth === 0) {
          rows.push(currentRow.trim() + ')');
          currentRow = "";
          continue;
        }
      }
    }
    
    if (depth > 0) {
      currentRow += char;
    }
  }
  
  return rows.map(row => {
    // Strip external brackets
    const inner = row.substring(1, row.length - 1);
    // Parse single quoted strings and numbers
    const columns = [];
    let currentVal = "";
    let inStr = false;
    let esc = false;
    
    for (let j = 0; j < inner.length; j++) {
      const c = inner[j];
      if (esc) {
        currentVal += c;
        esc = false;
        continue;
      }
      if (c === '\\') {
        currentVal += c;
        esc = true;
        continue;
      }
      if (c === "'") {
        inStr = !inStr;
        continue;
      }
      if (c === ',' && !inStr) {
        columns.push(currentVal.trim());
        currentVal = "";
        continue;
      }
      currentVal += c;
    }
    columns.push(currentVal.trim());
    return columns.map(v => v === 'NULL' ? null : v);
  });
}

async function seed() {
  console.log("Parsing database script data...");
  
  // 1. Parse Menu
  const rawMenu = parseInserts('menu', sqlContent);
  const menuItems = rawMenu.map(row => ({
    item_id: row[0],
    item_name: row[1],
    item_category: row[2],
    item_price: parseFloat(row[3]),
    item_description: row[4],
    status: row[5] || 'Active'
  }));

  // 2. Parse Accounts
  const rawAccounts = parseInserts('accounts', sqlContent);
  const accountsMap = new Map();
  rawAccounts.forEach(row => {
    accountsMap.set(row[0], {
      account_id: row[0],
      name: row[1],
      email: row[2],
      register_date: row[3],
      phone_number: row[4],
      salary: row[5] ? parseFloat(row[5]) : null,
      password: row[6],
      role: 'Member', // Default role
      points: 0
    });
  });

  // 3. Parse Staffs (Link to Accounts)
  const rawStaffs = parseInserts('staffs', sqlContent);
  rawStaffs.forEach(row => {
    const accId = row[3];
    if (accountsMap.has(accId)) {
      const acc = accountsMap.get(accId);
      acc.name = row[1];
      acc.role = row[2]; // Waiter, Chef, Manager, Admin
      acc.staff_id = row[0];
    }
  });

  // 4. Parse Memberships (Link to Accounts)
  const rawMemberships = parseInserts('memberships', sqlContent);
  rawMemberships.forEach(row => {
    const accId = row[3];
    if (accountsMap.has(accId)) {
      const acc = accountsMap.get(accId);
      acc.name = row[1];
      acc.role = 'Member';
      acc.points = parseInt(row[2]) || 0;
      acc.member_id = row[0];
    }
  });

  // 5. Parse Tables
  const rawTables = parseInserts('restaurant_tables', sqlContent);
  const tables = rawTables.map(row => ({
    table_id: row[0],
    capacity: parseInt(row[1]),
    is_available: row[2] === '1',
    table_type: row[3] || 'Standard'
  }));

  console.log(`Successfully parsed:`);
  console.log(`- ${menuItems.length} Menu items`);
  console.log(`- ${accountsMap.size} Unified User Accounts`);
  console.log(`- ${tables.length} Restaurant Tables`);
  
  console.log("\nSeeding menu collection to Firestore...");
  const menuBatch = db.batch();
  menuItems.forEach(item => {
    const docRef = db.collection('menu').doc(item.item_id);
    menuBatch.set(docRef, item);
  });
  await menuBatch.commit();
  console.log("Menu successfully seeded.");

  console.log("\nSeeding users collection to Firestore...");
  const usersBatch = db.batch();
  for (const [userId, user] of accountsMap.entries()) {
    const docRef = db.collection('users').doc(userId);
    usersBatch.set(docRef, user);
  }
  await usersBatch.commit();
  console.log("Users successfully seeded.");

  console.log("\nSeeding tables collection to Firestore...");
  const tablesBatch = db.batch();
  tables.forEach(table => {
    const docRef = db.collection('tables').doc(table.table_id);
    tablesBatch.set(docRef, table);
  });
  await tablesBatch.commit();
  console.log("Tables successfully seeded.");

  console.log("\n==========================================================");
  console.log("SUCCESS: Cloud Firestore Seeding completed successfully!");
  console.log("==========================================================");
}

seed().catch(err => {
  console.error("Migration seeding failed:", err);
});
