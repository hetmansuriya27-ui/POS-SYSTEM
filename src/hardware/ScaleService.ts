// src/hardware/ScaleService.ts

export class ScaleService {
  /**
   * Captures raw weigh scale digital serial streams, extracting floating balances.
   */
  static async readWeight(): Promise<number> {
    if (!("serial" in navigator)) {
      throw new Error("Web Serial API is not supported in this browser.");
    }

    // Spawns native port pairing popup
    const port = await (navigator as any).serial.requestPort();
    await port.open({ baudRate: 9600 });

    const textDecoder = new TextDecoderStream();
    const readableStreamClosed = port.readable.pipeTo(textDecoder.writable);
    const reader = textDecoder.readable.getReader();

    try {
      while (true) {
        const { value, done } = await reader.read();
        if (done) break;
        if (value) {
          // Parse values (e.g. ST,GS,  1.452,kg)
          const weightMatches = value.match(/[-+]?[0-9]*\.?[0-9]+/);
          if (weightMatches) {
            return parseFloat(weightMatches[0]); // Returns weight float
          }
        }
      }
    } finally {
      reader.releaseLock();
    }
    return 0;
  }
}
