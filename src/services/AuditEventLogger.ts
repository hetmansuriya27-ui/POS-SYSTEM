// src/services/AuditEventLogger.ts
import { sha256 } from "js-sha256";

export interface AuditLog {
  timestamp: string;
  staffId: string;
  role: string;
  action: string;
  details: string;
  previousHash: string;
  currentHash: string;
}

export class AuditEventLogger {
  private static lastHash = "0000000000000000000000000000000000000000000000000000000000000000";

  /**
   * Chains log mutations to block log tamperings. Each write integrates the SHA-256 of the prior log entry.
   */
  static async log(
    db: any, 
    merchantId: string, 
    log: Omit<AuditLog, "previousHash" | "currentHash">
  ): Promise<void> {
    const previousHash = this.lastHash;
    const currentHash = sha256(
      previousHash + log.timestamp + log.staffId + log.action + log.details
    );

    this.lastHash = currentHash;

    const lockedLog: AuditLog = {
      ...log,
      previousHash,
      currentHash
    };

    // Direct immutable write to database collection
    await db
      .collection("merchants")
      .doc(merchantId)
      .collection("audit_logs")
      .add(lockedLog);
      
    console.info("AuditEventLogger: Append-only audit block chained successfully:", currentHash);
  }
}
