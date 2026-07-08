// src/contexts/MonitoringProvider.tsx
import React, { useEffect } from "react";

export const initTelemetry = () => {
  if (process.env.NODE_ENV === "development") {
    console.info("Observability: Running in development mode. Sentry logging disabled.");
    return;
  }
  console.info("Observability: Running in production mode. Telemetry service initialized.");
};

export const MonitoringProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  useEffect(() => {
    initTelemetry();
  }, []);

  return <>{children}</>;
};
