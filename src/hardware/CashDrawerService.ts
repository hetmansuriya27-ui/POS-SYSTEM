// src/hardware/CashDrawerService.ts

export class CashDrawerService {
  /**
   * Sends raw direct kick drawer pulse commands (ESC p m t1 t2) to paired receipt printer device.
   */
  static async triggerDrawer(device: USBDevice): Promise<void> {
    await device.open();
    await device.selectConfiguration(1);
    await device.claimInterface(0);

    // ESC p 0 25 250 trigger pulse binary
    const kickCommand = new Uint8Array([0x1B, 0x70, 0x00, 0x19, 0xFA]);

    const endpoint = device.configuration!.interfaces[0].alternates[0].endpoints.find(
      ep => ep.direction === "out"
    );

    if (!endpoint) throw new Error("CashDrawerService: Outbound USB endpoint not detected.");
    await device.transferOut(endpoint.endpointNumber, kickCommand);
  }
}
