import { Injectable } from '@angular/core';
import { ApiService } from './api.service';
import { Observable, interval, switchMap, takeWhile, map } from 'rxjs';
import { ImportTaskStatus } from '@app/shared/models/import-result.model';

@Injectable({ providedIn: 'root' })
export class ImportService {
  constructor(private api: ApiService) {}

  importFile(file: File): Observable<{ taskId: number }> {
    return this.api.importFile(file).pipe(
      map(response => ({ taskId: response.task_id }))
    );
  }

  pollStatus(taskId: number, intervalMs: number = 2000): Observable<ImportTaskStatus> {
    return interval(intervalMs).pipe(
      switchMap(() => this.api.getImportStatus(taskId)),
      takeWhile(status =>
        status.status === 'pending' || status.status === 'processing',
        true
      )
    );
  }
}
