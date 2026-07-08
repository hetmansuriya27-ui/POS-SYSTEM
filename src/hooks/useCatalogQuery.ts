// src/hooks/useCatalogQuery.ts
import { useQuery } from "@tanstack/react-query";

export interface CatalogItem {
  id: string;
  name: string;
  category: string;
  price: number;
  barcode: string;
  stockLevel: number;
}

export const useCatalogQuery = (db: any, merchantId: string) => {
  return useQuery({
    queryKey: ["catalog", merchantId],
    queryFn: async (): Promise<CatalogItem[]> => {
      // Direct Firestore document read wrapper
      const snapshot = await db
        .collection("merchants")
        .doc(merchantId)
        .collection("catalog")
        .get();
        
      const items: CatalogItem[] = [];
      snapshot.forEach((doc: any) => {
        items.push({ id: doc.id, ...doc.data() } as CatalogItem);
      });
      return items;
    },
    staleTime: 1000 * 60 * 5, // Caches results for 5 minutes
    refetchOnWindowFocus: false
  });
};
