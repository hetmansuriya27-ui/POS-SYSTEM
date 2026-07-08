// src/enterprise/TenantLimitManager.ts

export type PlanTier = "SILVER" | "GOLD" | "ENTERPRISE";

export interface TenantLimits {
  maxTerminals: number;
  maxStores: number;
  maxCatalogItems: number;
}

export const TIER_LIMITS: Record<PlanTier, TenantLimits> = {
  SILVER: { maxTerminals: 2, maxStores: 1, maxCatalogItems: 150 },
  GOLD: { maxTerminals: 5, maxStores: 3, maxCatalogItems: 500 },
  ENTERPRISE: { maxTerminals: 99, maxStores: 99, maxCatalogItems: 9999 }
};

export class TenantLimitManager {
  /**
   * Enforces terminal counts limits, preventing register seats creation overflows.
   */
  static enforceTerminalCreation(currentCount: number, plan: PlanTier): boolean {
    const limits = TIER_LIMITS[plan];
    if (currentCount >= limits.maxTerminals) {
      console.warn(`SaaS Block: Active subscription plan (${plan}) limits terminal seats to ${limits.maxTerminals}.`);
      return false;
    }
    return true;
  }

  /**
   * Enforces outlet locations allocations.
   */
  static enforceStoreCreation(currentCount: number, plan: PlanTier): boolean {
    const limits = TIER_LIMITS[plan];
    if (currentCount >= limits.maxStores) {
      console.warn(`SaaS Block: Active subscription plan (${plan}) limits store outlets to ${limits.maxStores}.`);
      return false;
    }
    return true;
  }
}
