// src/offline/OfflineMutationStore.ts
import Dexie, { Table } from "dexie";

export interface OfflineBill {
  id?: string;
  invoiceNumber: string;
  timestamp: string;
  items: Array<{ itemId: string; name: string; quantity: number; price: number }>;
  subtotal: number;
  taxTotal: number;
  grandTotal: number;
  paymentMethod: "CASH" | "CARD" | "UPI";
  synced: number; // 0 = Pending, 1 = Synced
  updatedAt: number;
}

export interface OfflineCatalog {
  id: string;
  name: string;
  barcode: string;
  price: number;
  stockLevel: number;
  category: string;
  updatedAt: number;
}

class SaaSStoreOfflineDB extends Dexie {
  bills!: Table<OfflineBill>;
  catalog!: Table<OfflineCatalog>;

  constructor() {
    super("SaaSStoreOfflineDB");
    this.version(1).stores({
      bills: "++id, invoiceNumber, timestamp, synced, updatedAt",
      catalog: "id, name, barcode, category, updatedAt"
    });
  }
}

export const offlineDb = new SaaSStoreOfflineDB();
