const admin = require('firebase-admin');
const path = require('path');

const serviceAccountPath = path.join(__dirname, 'serviceAccountKey.json');
const serviceAccount = require(serviceAccountPath);

admin.initializeApp({
  credential: admin.credential.cert(serviceAccount)
});

const db = admin.firestore();

async function runTests() {
  console.log("=== RUNNING FIRESTORE POS QUERY DIAGNOSTICS ===");

  const tableId = 1;

  // Query 1: Active unpaid bills for a table
  try {
    console.log(`\nTesting Query 1: Active unpaid bills for table_id = ${tableId}...`);
    const snap1 = await db.collection("bills")
      .where("table_id", "==", tableId)
      .where("payment_time", "==", null)
      .limit(1)
      .get();
    console.log(`Query 1 Success! Found documents: ${snap1.size}`);
  } catch (err) {
    console.error("Query 1 Failed:", err.message);
  }

  // Query 2: Pending self-service customer orders for a table
  try {
    console.log(`\nTesting Query 2: Pending customer orders for table_id = ${tableId}...`);
    const snap2 = await db.collection("customer_orders")
      .where("table_id", "==", tableId)
      .where("status", "==", "Pending")
      .limit(1)
      .get();
    console.log(`Query 2 Success! Found documents: ${snap2.size}`);
  } catch (err) {
    console.error("Query 2 Failed:", err.message);
  }

  // Query 3: Latest bill order
  try {
    console.log("\nTesting Query 3: Latest bill ordered by bill_id desc...");
    const snap3 = await db.collection("bills")
      .orderBy("bill_id", "desc")
      .limit(1)
      .get();
    console.log(`Query 3 Success! Found documents: ${snap3.size}`);
    if (!snap3.empty) {
      console.log("Latest bill:", snap3.docs[0].id, "=>", snap3.docs[0].data());
    }
  } catch (err) {
    console.error("Query 3 Failed:", err.message);
  }

  // Query 4: Customer orders ordered by request_group_id desc
  try {
    console.log("\nTesting Query 4: Customer orders ordered by request_group_id desc...");
    const snap4 = await db.collection("customer_orders")
      .orderBy("request_group_id", "desc")
      .limit(1)
      .get();
    console.log(`Query 4 Success! Found documents: ${snap4.size}`);
    if (!snap4.empty) {
      console.log("Latest customer order:", snap4.docs[0].id, "=>", snap4.docs[0].data());
    }
  } catch (err) {
    console.error("Query 4 Failed:", err.message);
  }

  console.log("\n=== DIAGNOSTICS COMPLETED ===");
}

runTests().catch(err => console.error("Unhandled error:", err));
