import { Component } from '@angular/core';
import { RouterOutlet, RouterLink, RouterLinkActive } from '@angular/router';
import { CommonModule } from '@angular/common';
import { AuthService } from './core/services/auth.service';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [CommonModule, RouterOutlet, RouterLink, RouterLinkActive],
  template: `
    <nav class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center space-x-4">
            <a routerLink="/products" class="text-xl font-bold text-gray-900">Product Import</a>
            @if (auth.isLoggedIn()) {
              <a routerLink="/import" routerLinkActive="bg-gray-100" routerLinkActive="text-blue-700"
                 class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100">
                Import
              </a>
              <a routerLink="/products" routerLinkActive="bg-gray-100" routerLinkActive="text-blue-700"
                 class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100">
                Products
              </a>
            }
          </div>
          <div class="flex items-center">
            @if (auth.isLoggedIn()) {
              <span class="text-sm text-gray-500 mr-4">{{ auth.currentUser()?.email }}</span>
              <button (click)="auth.logout()"
                      class="text-sm text-red-600 hover:text-red-800 font-medium">
                Logout
              </button>
            } @else {
              <a routerLink="/login" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                Login
              </a>
            }
          </div>
        </div>
      </div>
    </nav>
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <router-outlet />
    </main>
  `,
})
export class AppComponent {
  constructor(public auth: AuthService) {}
}
