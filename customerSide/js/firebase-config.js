// Your web app's Firebase configuration
const firebaseConfig = {
  apiKey: "AIzaSyAmRxw6SoATvI7mKDXBbn4KpwHw3s3np9A",
  authDomain: "pos-system-1276b.firebaseapp.com",
  projectId: "pos-system-1276b",
  storageBucket: "pos-system-1276b.firebasestorage.app",
  messagingSenderId: "396342956479",
  appId: "1:396342956479:web:e53e7eb456425c99a5510a",
  measurementId: "G-SC97M6HFB4"
};

// Initialize Firebase globally using the Compat SDK
if (typeof firebase !== 'undefined') {
  firebase.initializeApp(firebaseConfig);
  window.db = firebase.firestore();
  window.auth = firebase.auth();
  window.analytics = firebase.analytics();
  console.log("Firebase initialized successfully.");
} else {
  console.error("Firebase SDK was not loaded. Please make sure to include the CDN scripts before firebase-config.js.");
}
