import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { CommonModule } from '@angular/common';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';

@Component({
  selector: 'app-daily-work-log-id',
  imports: [
    CommonModule,
    MatButtonModule,
    MatIconModule
  ],
  templateUrl: './daily-work-log-id.html',
  styleUrl: './daily-work-log-id.css'
})
export class DailyWorkLogId implements OnInit {
  
  workLogId: string | null = null;

  constructor(private route: ActivatedRoute) {}

  ngOnInit() {
    this.workLogId = this.route.snapshot.paramMap.get('id');
  }
}