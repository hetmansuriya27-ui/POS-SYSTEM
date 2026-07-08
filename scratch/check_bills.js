const admin = require('firebase-admin');
const path = require('path');

const serviceAccountPath = path.join(__dirname, 'serviceAccountKey.json');
const serviceAccount = require(serviceAccountPath);

admin.initializeApp({
  credential: admin.credential.cert(serviceAccount)
});

const db = admin.firestore();

async function check() {
  console.log("=== CHECKING FIREBASE DATA ===");
  
  // 1. Check Menu
  const menuSnap = await db.collection("menu").limit(3).get();
  console.log(`\nMenu Items Found: ${menuSnap.size}`);
  menuSnap.forEach(doc => {
    console.log(doc.id, "=>", doc.data());
  });

  // 2. Check Tables
  const tableSnap = await db.collection("tables").limit(3).get();
  console.log(`\nTables Found: ${tableSnap.size}`);
  tableSnap.forEach(doc => {
    console.log(doc.id, "=>", doc.data());
  });

  // 3. Check Bills
  const billsSnap = await db.collection("bills").orderBy("bill_id", "desc").limit(5).get();
  console.log(`\nRecent Bills Found: ${billsSnap.size}`);
  billsSnap.forEach(doc => {
    console.log(doc.id, "=>", doc.data());
  });

  console.log("\n=== COMPLETED ===");
}

check().catch(err => console.error("Error checking:", err));
