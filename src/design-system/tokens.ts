// design-system/tokens.ts
// Premium design tokens inspired by Stripe, Linear, and Vercel

export const tokens = {
  colors: {
    dark: {
      background: "222 47% 11%", // #0F172A
      foreground: "213 27% 84%",
      primary: "221 83% 53%", // #2563EB
      secondary: "244 75% 59%", // #4F46E5
      success: "142 70% 45%", // #10B981
      warning: "38 92% 50%", // #F59E0B
      danger: "350 89% 60%", // #EF4444
      border: "217.2 32.6% 17.5%",
      card: "222 47% 6%",
    },
    light: {
      background: "210 40% 98%", // #F8FAFC
      foreground: "222 47% 11%",
      primary: "221 83% 53%",
      secondary: "244 75% 59%",
      success: "142 70% 45%",
      warning: "38 92% 50%",
      danger: "350 89% 60%",
      border: "214.3 31.8% 91.4%",
      card: "0 0% 100%",
    }
  },
  typography: {
    fontSans: "'Inter', sans-serif",
    fontHeading: "'Outfit', sans-serif",
    fontMono: "'JetBrains Mono', monospace",
    sizes: {
      xs: "0.75rem", // 12px
      sm: "0.875rem", // 14px
      base: "1rem", // 16px
      lg: "1.125rem", // 18px
      xl: "1.25rem", // 20px
      "2xl": "1.5rem", // 24px
      "3xl": "1.875rem", // 30px
      "4xl": "2.25rem" // 36px
    }
  },
  shadows: {
    premium: "0 8px 32px 0 rgba(0, 0, 0, 0.37)",
    glow: "0 0 20px rgba(37, 99, 235, 0.25)",
    soft: "0 4px 12px 0 rgba(0, 0, 0, 0.05)"
  },
  transitions: {
    spring: "all 0.3s cubic-bezier(0.4, 0, 0.2, 1)",
    fast: "all 0.15s ease"
  }
};
