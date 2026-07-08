// src/hardware/BarcodeScannerService.ts

export class BarcodeScannerService {
  private static buffer = "";
  private static lastKeyTime = 0;

  /**
   * Filters raw keyboard entries. Emulated laser barcode scans aggregate keys within 50ms intervals.
   */
  static handleEvent(e: KeyboardEvent, onScan: (barcode: string) => void): void {
    const now = Date.now();
    
    // Key aggregations duration gate
    if (now - this.lastKeyTime > 50) {
      this.buffer = "";
    }
    this.lastKeyTime = now;

    if (e.key === "Enter") {
      if (this.buffer.length > 3) {
        onScan(this.buffer);
        this.buffer = "";
        e.preventDefault();
      }
    } else if (e.key.length === 1) {
      this.buffer += e.key;
    }
  }
}
