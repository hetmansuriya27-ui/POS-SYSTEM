// src/hardware/PrinterService.ts

export interface ReceiptItem {
  name: string;
  quantity: number;
  price: number;
}

export interface ReceiptPayload {
  merchantName: string;
  invoiceNo: string;
  cashier: string;
  items: ReceiptItem[];
  tax: number;
  total: number;
}

export class PrinterService {
  private static ESC = 0x1B;
  private static GS = 0x1D;

  /**
   * Directly interfaces with paired WebUSB thermal printer device, bypassing local spoolers.
   */
  static async printReceipt(device: USBDevice, receipt: ReceiptPayload): Promise<void> {
    await device.open();
    await device.selectConfiguration(1);
    await device.claimInterface(0);

    const encoder = new TextEncoder();
    
    // Compile direct ESC/POS binaries
    const commandList = [
      this.ESC, 0x40, // Initialize printer
      this.ESC, 0x61, 0x01, // Center alignment
      ...encoder.encode(`${receipt.merchantName}\n`),
      ...encoder.encode("GSTIN: 27AAAAA0000A1Z5\n"),
      ...encoder.encode("--------------------------------\n"),
      this.ESC, 0x61, 0x00, // Left alignment
      ...encoder.encode(`Invoice: ${receipt.invoiceNo}\n`),
      ...encoder.encode(`Cashier: ${receipt.cashier}\n`),
      ...encoder.encode("--------------------------------\n"),
      ...encoder.encode("Item            Qty      Total\n"),
      ...receipt.items.map(item => 
        ...encoder.encode(`${item.name.padEnd(16)} x${item.quantity.toString().padEnd(4)} $${(item.price * item.quantity).toFixed(2)}\n`)
      ).flat(),
      ...encoder.encode("--------------------------------\n"),
      this.ESC, 0x61, 0x02, // Right alignment
      ...encoder.encode(`GST (18%): $${receipt.tax.toFixed(2)}\n`),
      ...encoder.encode(`Grand Total: $${receipt.total.toFixed(2)}\n\n`),
      this.ESC, 0x61, 0x01,
      ...encoder.encode("Thank you for your visit!\n\n\n"),
      this.GS, 0x56, 0x42, 0x00 // Cut paper trigger
    ];

    const bytes = new Uint8Array(commandList);

    const endpoint = device.configuration!.interfaces[0].alternates[0].endpoints.find(
      ep => ep.direction === "out"
    );

    if (!endpoint) throw new Error("PrinterService: Outbound USB endpoint not detected.");
    await device.transferOut(endpoint.endpointNumber, bytes);
  }
}
