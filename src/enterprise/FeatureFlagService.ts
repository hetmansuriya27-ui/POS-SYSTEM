// src/enterprise/FeatureFlagService.ts
import { PlanTier } from "./TenantLimitManager";

export interface FeatureFlags {
  enableAIDemandForecasting: boolean;
  enableMultiStoreReporting: boolean;
  enableHardwareDirectSpool: boolean;
}

export const TIER_FEATURES: Record<PlanTier, FeatureFlags> = {
  SILVER: {
    enableAIDemandForecasting: false,
    enableMultiStoreReporting: false,
    enableHardwareDirectSpool: false
  },
  GOLD: {
    enableAIDemandForecasting: false,
    enableMultiStoreReporting: true,
    enableHardwareDirectSpool: true
  },
  ENTERPRISE: {
    enableAIDemandForecasting: true,
    enableMultiStoreReporting: true,
    enableHardwareDirectSpool: true
  }
};

export class FeatureFlagService {
  /**
   * Validates if a feature is unlocked for a merchant based on their pricing subscription tier.
   */
  static isFeatureEnabled(feature: keyof FeatureFlags, plan: PlanTier): boolean {
    return TIER_FEATURES[plan]?.[feature] || false;
  }
}
