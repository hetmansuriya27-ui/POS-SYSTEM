// src/offline/SyncQueue.ts
import { offlineDb } from "./OfflineMutationStore";

export class SyncQueue {
  private static isSyncing = false;
  private static retryDelay = 2000;

  static initialize(db: any, merchantId: string) {
    window.addEventListener("online", () => {
      console.log("SyncQueue: Connection restored. Flushing queued transactions...");
      this.processQueue(db, merchantId);
    });
    window.addEventListener("offline", () => {
      console.warn("SyncQueue: Device went OFFLINE. Operations buffering locally.");
    });
    
    // Auto-run in case page refreshed while online
    if (navigator.onLine) {
      this.processQueue(db, merchantId);
    }
  }

  static async processQueue(db: any, merchantId: string): Promise<void> {
    if (this.isSyncing || !navigator.onLine) return;
    this.isSyncing = true;

    try {
      const pending = await offlineDb.bills.where("synced").equals(0).toArray();
      if (pending.length === 0) {
        this.isSyncing = false;
        return;
      }

      console.log(`SyncQueue: Aggregated ${pending.length} pending checkouts. Uploading...`);

      for (const bill of pending) {
        const docRef = db
          .collection("merchants")
          .doc(merchantId)
          .collection("bills")
          .doc(bill.invoiceNumber);

        // 1. Commit server write
        await docRef.set({
          ...bill,
          synced: 1,
          syncedAt: new Date().toISOString()
        });

        // 2. Mark local item as synced
        await offlineDb.bills.update(bill.id!, { synced: 1 });
      }

      console.log("SyncQueue: All offline mutations synced with central server.");
      this.retryDelay = 2000; // Reset backoff delay on successful write
    } catch (err) {
      console.error("SyncQueue: Network push failed. Backing off...", err);
      // Exponential backoff capped at 30 seconds
      this.retryDelay = Math.min(this.retryDelay * 2, 30000);
      setTimeout(() => this.processQueue(db, merchantId), this.retryDelay);
    } finally {
      this.isSyncing = false;
    }
  }
}
