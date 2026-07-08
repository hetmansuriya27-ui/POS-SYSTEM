// src/security/PermissionGate.tsx
import React from "react";
import { UserRole, hasPermission } from "./RoleMatrix";

interface PermissionGateProps {
  children: React.ReactNode;
  userRole: UserRole;
  action: string;
  fallback?: React.ReactNode;
}

export const PermissionGate: React.FC<PermissionGateProps> = ({ 
  children, 
  userRole, 
  action, 
  fallback = null 
}) => {
  if (!hasPermission(userRole, action)) {
    return <>{fallback}</>;
  }
  return <>{children}</>;
};
