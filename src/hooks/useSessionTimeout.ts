// src/hooks/useSessionTimeout.ts
import { useEffect, useRef } from "react";

/**
 * Monitors UI user activity events. Calls timeout handler if inactive for timeoutMs limit.
 */
export const useSessionTimeout = (onTimeout: () => void, timeoutMs: number = 300000) => {
  const timerRef = useRef<NodeJS.Timeout | null>(null);

  const resetTimer = () => {
    if (timerRef.current) clearTimeout(timerRef.current);
    timerRef.current = setTimeout(onTimeout, timeoutMs);
  };

  useEffect(() => {
    const events = ["mousedown", "mousemove", "keypress", "scroll", "touchstart"];
    
    // Register actions hooks
    events.forEach(event => window.addEventListener(event, resetTimer));
    resetTimer();

    return () => {
      events.forEach(event => window.removeEventListener(event, resetTimer));
      if (timerRef.current) clearTimeout(timerRef.current);
    };
  }, [onTimeout, timeoutMs]);
};
