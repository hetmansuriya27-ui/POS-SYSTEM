const admin = require('firebase-admin');
const path = require('path');

const serviceAccountPath = path.join(__dirname, 'serviceAccountKey.json');
const serviceAccount = require(serviceAccountPath);

admin.initializeApp({
  credential: admin.credential.cert(serviceAccount)
});

const db = admin.firestore();

async function testCreateBill() {
  console.log("=== SIMULATING BILL CREATION FOR TABLE 2 ===");

  const tableId = 2;
  const billId = "1002";

  try {
    // 1. Check if table 2 exists in tables collection
    const tableDoc = await db.collection("tables").doc(tableId.toString()).get();
    console.log(`Table ${tableId} exists in tables collection:`, tableDoc.exists);
    if (tableDoc.exists) {
      console.log("Table data:", tableDoc.data());
    }

    // 2. Create the bill document
    const newBill = {
      bill_id: billId,
      table_id: tableId,
      bill_time: new Date().toISOString(),
      payment_time: null,
      non_member_name: "Test POS Guest",
      non_member_mobile: "1234",
      items_ordered: [],
      total_amount: 0
    };

    console.log(`Writing bill document ${billId} for table ${tableId}...`);
    await db.collection("bills").doc(billId).set(newBill);
    console.log("Bill created successfully!");

    // Clean up
    await db.collection("bills").doc(billId).delete();
    console.log("Cleanup: Bill deleted successfully.");

  } catch (err) {
    console.error("Simulation failed:", err.message);
  }

  console.log("\n=== COMPLETED ===");
}

testCreateBill().catch(err => console.error("Unhandled error:", err));
