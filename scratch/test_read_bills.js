const admin = require('firebase-admin');
const path = require('path');

const serviceAccountPath = path.join(__dirname, 'serviceAccountKey.json');
const serviceAccount = require(serviceAccountPath);

admin.initializeApp({
  credential: admin.credential.cert(serviceAccount)
});

const db = admin.firestore();

async function simulateResolveBill(tableId, billId) {
  console.log(`\n=== SIMULATING resolveBill FOR TABLE ${tableId} AND BILL ${billId} ===`);
  
  let activeBill = null;

  try {
    if (billId && billId !== "null" && billId !== "NULL") {
      console.log(`Fetching pre-specified bill doc: ${billId}...`);
      const billDoc = await db.collection("bills").doc(billId).get();
      if (billDoc.exists) {
        activeBill = billDoc.data();
        console.log("Found pre-specified bill:", activeBill);
        return;
      }
      console.log("Pre-specified bill does not exist.");
    }

    console.log(`Querying active unpaid bills for table ${tableId}...`);
    const activeBillsSnap = await db.collection("bills")
        .where("table_id", "==", tableId)
        .where("payment_time", "==", null)
        .limit(1)
        .get();

    if (!activeBillsSnap.empty) {
      activeBill = activeBillsSnap.docs[0].data();
      billId = activeBillsSnap.docs[0].id;
      console.log(`Found existing unpaid bill in DB: ${billId}`, activeBill);
    } else {
      console.log("No unpaid bill found. Simulating createNewBill()...");
      
      let nextBillId = 1001;
      console.log("Fetching latest bill by bill_id desc...");
      const latestBillQuery = await db.collection("bills")
          .orderBy("bill_id", "desc")
          .limit(1)
          .get();

      if (!latestBillQuery.empty) {
        const latestId = parseInt(latestBillQuery.docs[0].data().bill_id);
        console.log(`Latest bill ID in DB is: ${latestId}`);
        if (!isNaN(latestId)) {
          nextBillId = latestId + 1;
        }
      } else {
        console.log("No bills exist in DB yet.");
      }

      billId = nextBillId.toString();
      const newBill = {
        bill_id: billId,
        table_id: tableId,
        bill_time: new Date().toISOString(),
        payment_time: null,
        non_member_name: "",
        non_member_mobile: "",
        items_ordered: [],
        total_amount: 0
      };

      console.log(`Saving new bill doc ${billId} for table ${tableId}...`);
      await db.collection("bills").doc(billId).set(newBill);
      activeBill = newBill;
      console.log("New bill created successfully:", activeBill);

      // Clean up the created bill so we don't pollute database
      await db.collection("bills").doc(billId).delete();
      console.log("Cleanup: Deleted simulated bill.");
    }
  } catch (err) {
    console.error("Error in simulation:", err);
  }
}

async function run() {
  await simulateResolveBill(2, "null");
  console.log("\n=== SIMULATION COMPLETED ===");
}

run().catch(err => console.error(err));
