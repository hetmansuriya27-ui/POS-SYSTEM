// src/security/RoleMatrix.ts

export type UserRole = "CASHIER" | "CHEF" | "MANAGER" | "OWNER";

export interface Permission {
  action: 
    | "VIEW_BILLING" 
    | "VOID_ITEM" 
    | "ISSUE_REFUND" 
    | "MANAGE_INVENTORY" 
    | "VIEW_REPORTS" 
    | "GLOBAL_CONFIG";
}

export const ROLE_MATRIX: Record<UserRole, string[]> = {
  CASHIER: ["VIEW_BILLING"],
  CHEF: [],
  MANAGER: ["VIEW_BILLING", "VOID_ITEM", "MANAGE_INVENTORY"],
  OWNER: [
    "VIEW_BILLING", 
    "VOID_ITEM", 
    "ISSUE_REFUND", 
    "MANAGE_INVENTORY", 
    "VIEW_REPORTS", 
    "GLOBAL_CONFIG"
  ]
};

/**
 * Validates if an employee role has permission to execute a specific action.
 */
export function hasPermission(role: UserRole, action: string): boolean {
  return ROLE_MATRIX[role]?.includes(action) || false;
}
