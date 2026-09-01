export interface ImportResult {
  total_rows: number;
  imported: number;
  updated: number;
  skipped: number;
  errors: ImportError[];
  errors_count: number;
}

export interface ImportError {
  row: number;
  error: string;
}

export interface ImportTaskStatus {
  status: 'pending' | 'processing' | 'completed' | 'completed_with_errors' | 'failed';
  result: ImportResult | null;
}
