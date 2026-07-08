// src/security/ProtectedRoute.tsx
import React from "react";
import { Navigate } from "react-router-dom";
import { UserRole, hasPermission } from "./RoleMatrix";

interface ProtectedRouteProps {
  children: React.ReactNode;
  user: { role: UserRole } | null;
  requiredPermission?: string;
}

export const ProtectedRoute: React.FC<ProtectedRouteProps> = ({ 
  children, 
  user, 
  requiredPermission 
}) => {
  // 1. If cashier isn't logged in, redirect to PIN lock interface
  if (!user) {
    return <Navigate to="/lock" replace />;
  }

  // 2. Verify roles against required page privileges
  if (requiredPermission && !hasPermission(user.role, requiredPermission)) {
    return <Navigate to="/unauthorized" replace />;
  }

  return <>{children}</>;
};
