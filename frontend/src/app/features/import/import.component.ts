import { Component, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ImportService } from '@app/core/services/import.service';
import { ImportTaskStatus } from '@app/shared/models/import-result.model';

@Component({
  selector: 'app-import',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div class="max-w-2xl mx-auto">
      <h1 class="text-2xl font-bold text-gray-900 mb-6">Import Products</h1>

      <!-- Upload Area -->
      <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center
                    hover:border-blue-400 transition-colors">
          <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                  stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
          <input type="file" #fileInput accept=".xlsx,.xls" (change)="onFileSelect($event)"
                 class="hidden" />
          <button (click)="fileInput.click()"
                  class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm
                         font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700
                         focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500
                         transition-colors">
            Select XLSX File
          </button>
          @if (selectedFile()) {
            <p class="mt-4 text-sm text-gray-600">
              Selected: <span class="font-medium">{{ selectedFile()!.name }}</span>
              ({{ formatSize(selectedFile()!.size) }})
            </p>
          }
        </div>

        @if (selectedFile()) {
          <button (click)="upload()" [disabled]="uploading()"
                  class="mt-4 w-full flex justify-center py-2 px-4 border border-transparent rounded-md
                         shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700
                         focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500
                         disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
            @if (uploading()) {
              <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
              </svg>
              Processing...
            } @else {
              Start Import
            }
          </button>
        }
      </div>

      <!-- Progress -->
      @if (taskStatus()) {
        <div class="bg-white shadow rounded-lg p-6 mb-6">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Import Progress</h3>
          <div class="flex items-center">
            @if (taskStatus()!.status === 'pending' || taskStatus()!.status === 'processing') {
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mr-4"></div>
              <span class="text-gray-600">
                {{ taskStatus()!.status === 'pending' ? 'Waiting in queue...' : 'Processing file...' }}
              </span>
            } @else if (taskStatus()!.status === 'completed') {
              <div class="flex items-center text-green-600">
                <svg class="h-8 w-8 mr-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="font-medium">Import Completed</span>
              </div>
            } @else {
              <div class="flex items-center text-yellow-600">
                <svg class="h-8 w-8 mr-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
                <span class="font-medium">Import Finished with Errors</span>
              </div>
            }
          </div>
        </div>
      }

      <!-- Results -->
      @if (taskStatus()?.result) {
        <div class="bg-white shadow rounded-lg p-6">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Results</h3>
          <dl class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-gray-50 rounded-lg p-4">
              <dt class="text-sm text-gray-500">Total Rows</dt>
              <dd class="text-2xl font-bold text-gray-900">{{ taskStatus()!.result!.total_rows }}</dd>
            </div>
            <div class="bg-green-50 rounded-lg p-4">
              <dt class="text-sm text-green-600">Imported</dt>
              <dd class="text-2xl font-bold text-green-600">{{ taskStatus()!.result!.imported }}</dd>
            </div>
            <div class="bg-blue-50 rounded-lg p-4">
              <dt class="text-sm text-blue-600">Updated</dt>
              <dd class="text-2xl font-bold text-blue-600">{{ taskStatus()!.result!.updated }}</dd>
            </div>
            <div class="bg-yellow-50 rounded-lg p-4">
              <dt class="text-sm text-yellow-600">Skipped</dt>
              <dd class="text-2xl font-bold text-yellow-600">{{ taskStatus()!.result!.skipped }}</dd>
            </div>
          </dl>

          @if (taskStatus()!.result!.errors.length > 0) {
            <div>
              <h4 class="text-sm font-medium text-red-800 mb-2">
                Errors ({{ taskStatus()!.result!.errors_count }})
              </h4>
              <div class="max-h-64 overflow-y-auto border border-red-200 rounded-md">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-red-50 sticky top-0">
                    <tr>
                      <th class="px-4 py-2 text-left text-xs font-medium text-red-700">Row</th>
                      <th class="px-4 py-2 text-left text-xs font-medium text-red-700">Error</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    @for (error of taskStatus()!.result!.errors; track error.row) {
                      <tr>
                        <td class="px-4 py-2 text-sm text-gray-900 whitespace-nowrap">{{ error.row }}</td>
                        <td class="px-4 py-2 text-sm text-red-600">{{ error.error }}</td>
                      </tr>
                    }
                  </tbody>
                </table>
              </div>
            </div>
          }
        </div>
      }
    </div>
  `,
})
export class ImportComponent {
  selectedFile = signal<File | null>(null);
  uploading = signal(false);
  taskStatus = signal<ImportTaskStatus | null>(null);

  constructor(private importService: ImportService) {}

  onFileSelect(event: Event): void {
    const input = event.target as HTMLInputElement;
    if (input.files?.length) {
      this.selectedFile.set(input.files[0]);
    }
  }

  upload(): void {
    const file = this.selectedFile();
    if (!file) return;

    this.uploading.set(true);

    this.importService.importFile(file).subscribe({
      next: ({ taskId }) => {
        this.importService.pollStatus(taskId).subscribe(status => {
          this.taskStatus.set(status);
          if (status.status !== 'pending' && status.status !== 'processing') {
            this.uploading.set(false);
          }
        });
      },
      error: () => {
        this.uploading.set(false);
      },
    });
  }

  formatSize(bytes: number): string {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }
}
