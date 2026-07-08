// src/components/observability/ToastProvider.tsx
import React from "react";
import { useStore } from "../../store/useStore";
import { X, CheckCircle, AlertTriangle } from "lucide-react";

export const ToastProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const toasts = useStore((state) => state.toasts);
  const removeToast = useStore((state) => state.removeToast);

  return (
    <>
      {children}
      <div className="fixed top-6 right-6 z-50 flex flex-col gap-3 w-80 pointer-events-none">
        {toasts.map((toast) => (
          <div
            key={toast.id}
            className={`p-4 rounded-xl flex items-center justify-between border backdrop-filter blur-md pointer-events-auto shadow-lg transition-all duration-300 ${
              toast.type === "success" 
                ? "bg-emerald-500/15 border-emerald-500/30 text-emerald-400" 
                : toast.type === "warning" 
                ? "bg-amber-500/15 border-amber-500/30 text-amber-400" 
                : "bg-red-500/15 border-red-500/30 text-red-400"
            }`}
          >
            <div className="flex items-center gap-3 text-xs font-bold">
              {toast.type === "success" ? (
                <CheckCircle size={16} />
              ) : (
                <AlertTriangle size={16} />
              )}
              <span>{toast.message}</span>
            </div>
            <button 
              onClick={() => removeToast(toast.id)}
              className="text-slate-400 hover:text-white transition-colors"
            >
              <X size={14} />
            </button>
          </div>
        ))}
      </div>
    </>
  );
};
