const admin = require('firebase-admin');
const path = require('path');
const fs = require('fs');

const serviceAccountPath = path.join(__dirname, 'serviceAccountKey.json');
if (!fs.existsSync(serviceAccountPath)) {
  console.error("ERROR: serviceAccountKey.json not found!");
  process.exit(1);
}

const serviceAccount = require(serviceAccountPath);
admin.initializeApp({
  credential: admin.credential.cert(serviceAccount)
});

const db = admin.firestore();

async function main() {
  console.log("Fetching all members from Firestore 'users' collection...");
  const snapshot = await db.collection('users').where('role', '==', 'Member').get();
  
  if (snapshot.empty) {
    console.log("No members found in Firestore.");
    return;
  }
  
  console.log(`Found ${snapshot.size} members. Deleting...`);
  const batch = db.batch();
  snapshot.forEach(doc => {
    console.log(`Deleting member account #${doc.id} (${doc.data().email || doc.data().name || 'No Name'})`);
    batch.delete(doc.ref);
  });
  
  await batch.commit();
  console.log("Successfully deleted all members from Firestore!");
}

main().catch(err => {
  console.error("Error deleting members from Firestore:", err);
  process.exit(1);
});
