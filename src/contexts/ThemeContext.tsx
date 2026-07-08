// src/contexts/ThemeContext.tsx
import React, { createContext, useContext, useEffect, useState } from "react";

export interface TenantBrandConfig {
  primaryHex: string;
  secondaryHex: string;
  borderRadiusFactor: number;
}

interface ThemeContextType {
  theme: "dark" | "light";
  toggleTheme: () => void;
  brand: TenantBrandConfig;
  updateBrand: (config: TenantBrandConfig) => void;
}

const ThemeContext = createContext<ThemeContextType | undefined>(undefined);

export const ThemeProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [theme, setTheme] = useState<"dark" | "light">("dark");
  const [brand, setBrand] = useState<TenantBrandConfig>({
    primaryHex: "#2563EB",
    secondaryHex: "#4F46E5",
    borderRadiusFactor: 16
  });

  useEffect(() => {
    // 1. Enforce theme class on document root
    const root = window.document.documentElement;
    root.classList.remove("light", "dark");
    root.classList.add(theme);
  }, [theme]);

  useEffect(() => {
    // 2. Inject dynamic merchant styles into active Tailwind CSS HSL variables
    const root = window.document.documentElement;
    root.style.setProperty("--primary", hexToHslString(brand.primaryHex));
    root.style.setProperty("--secondary", hexToHslString(brand.secondaryHex));
    root.style.setProperty("--radius-factor", brand.borderRadiusFactor.toString());
  }, [brand]);

  const toggleTheme = () => setTheme(prev => (prev === "dark" ? "light" : "dark"));

  return (
    <ThemeContext.Provider value={{ theme, toggleTheme, brand, updateBrand: setBrand }}>
      {children}
    </ThemeContext.Provider>
  );
};

export const useTheme = () => {
  const context = useContext(ThemeContext);
  if (!context) throw new Error("useTheme must be used within a ThemeProvider");
  return context;
};

// High-fidelity HEX to HSL string compiler
function hexToHslString(hex: string): string {
  let r = 0, g = 0, b = 0;
  if (hex.length === 4) {
    r = parseInt(hex[1] + hex[1], 16);
    g = parseInt(hex[2] + hex[2], 16);
    b = parseInt(hex[3] + hex[3], 16);
  } else if (hex.length === 7) {
    r = parseInt(hex.substring(1, 3), 16);
    g = parseInt(hex.substring(3, 5), 16);
    b = parseInt(hex.substring(5, 7), 16);
  }
  r /= 255; g /= 255; b /= 255;
  const max = Math.max(r, g, b), min = Math.min(r, g, b);
  let h = 0, s = 0, l = (max + min) / 2;
  if (max !== min) {
    const d = max - min;
    s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
    switch (max) {
      case r: h = (g - b) / d + (g < b ? 6 : 0); break;
      case g: h = (b - r) / d + 2; break;
      case b: h = (r - g) / d + 4; break;
    }
    h /= 6;
  }
  return `${Math.round(h * 360)} ${Math.round(s * 100)}% ${Math.round(l * 100)}%`;
}
