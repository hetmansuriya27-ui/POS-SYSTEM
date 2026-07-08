// src/store/useStore.ts
import { create } from "zustand";

export interface CartItem {
  id: string;
  name: string;
  price: number;
  quantity: number;
}

export interface CashierSession {
  staffId: string;
  name: string;
  role: "CASHIER" | "CHEF" | "MANAGER" | "OWNER";
  activeRegisterId: string;
}

interface ToastMessage {
  id: string;
  type: "success" | "warning" | "danger";
  message: string;
}

interface SaaSStoreState {
  cart: CartItem[];
  session: CashierSession | null;
  isTerminalLocked: boolean;
  toasts: ToastMessage[];
  
  // Toasts Actions
  addToast: (type: ToastMessage["type"], message: string) => void;
  removeToast: (id: string) => void;

  // Cart Actions
  addToCart: (product: Omit<CartItem, "quantity">) => void;
  updateCartQty: (id: string, qty: number) => void;
  removeFromCart: (id: string) => void;
  clearCart: () => void;

  // Session Actions
  setSession: (session: CashierSession | null) => void;
  lockTerminal: () => void;
  unlockTerminal: (pin: string) => Promise<boolean>;
}

export const useStore = create<SaaSStoreState>((set) => ({
  cart: [],
  session: null,
  isTerminalLocked: false,
  toasts: [],

  addToast: (type, message) => set((state) => {
    const id = Math.random().toString();
    // Auto-remove toasts in 4 seconds
    setTimeout(() => {
      set((s) => ({ toasts: s.toasts.filter(t => t.id !== id) }));
    }, 4000);
    return { toasts: [...state.toasts, { id, type, message }] };
  }),

  removeToast: (id) => set((state) => ({
    toasts: state.toasts.filter(t => t.id !== id)
  })),

  addToCart: (product) => set((state) => {
    const existing = state.cart.find(item => item.id === product.id);
    if (existing) {
      return {
        cart: state.cart.map(item => 
          item.id === product.id ? { ...item, quantity: item.quantity + 1 } : item
        )
      };
    }
    return { cart: [...state.cart, { ...product, quantity: 1 }] };
  }),

  updateCartQty: (id, qty) => set((state) => ({
    cart: qty <= 0 
      ? state.cart.filter(item => item.id !== id)
      : state.cart.map(item => item.id === id ? { ...item, quantity: qty } : item)
  })),

  removeFromCart: (id) => set((state) => ({
    cart: state.cart.filter(item => item.id !== id)
  })),

  clearCart: () => set({ cart: [] }),

  setSession: (session) => set({ session }),
  
  lockTerminal: () => set({ isTerminalLocked: true }),

  unlockTerminal: async (pin) => {
    // Standard terminal authentication check matching staff PIN
    const mockStaffDirectory: Record<string, { pin: string }> = {
      "1234": { pin: "1234" }, // Cashier PIN
      "9981": { pin: "9981" }  // Manager PIN
    };
    const matched = mockStaffDirectory[pin];
    if (matched) {
      set({ isTerminalLocked: false });
      return true;
    }
    return false;
  }
}));
