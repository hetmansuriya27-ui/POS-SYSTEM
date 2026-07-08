// src/offline/ConflictResolutionService.ts

export class ConflictResolutionService {
  /**
   * Resolves conflicts between local changes and server changes using a Last-Write-Wins (LWW) strategy.
   * @param local The local state object containing an `updatedAt` timestamp.
   * @param server The server state object containing an `updatedAt` timestamp.
   * @returns The winning object based on the latest timestamp.
   */
  static resolveLWW<T extends { updatedAt: number }>(local: T, server: T): T {
    if (local.updatedAt >= server.updatedAt) {
      console.info("LWW Conflict Resolved: Local update chosen as primary.", { local, server });
      return local;
    }
    console.info("LWW Conflict Resolved: Server update chosen as primary.", { local, server });
    return server;
  }
}
