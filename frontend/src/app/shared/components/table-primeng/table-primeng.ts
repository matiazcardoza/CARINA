// import { Component, OnInit } from '@angular/core';
// import { Customer } from '@/domain/customer';
// import { CustomerService } from '@/service/customerservice';
import { TableModule } from 'primeng/table';
import { HttpClientModule } from '@angular/common/http';
import { InputTextModule } from 'primeng/inputtext';
import { IconField } from 'primeng/iconfield';
import { InputIcon } from 'primeng/inputicon';
import { Tag } from 'primeng/tag';
// import clients
// import { clients } from './utils/mockup-data.js
import { clients } from './utils/mockup-data';
// ----------------------
// en tu .ts
import { FilterMetadata } from 'primeng/api';


import { Component, OnInit, signal } from '@angular/core';

@Component({
  selector: 'app-table-primeng',
  imports: [TableModule, InputTextModule, Tag, IconField, InputIcon],
  templateUrl: './table-primeng.html',
  styleUrl: './table-primeng.css'
})
export class TablePrimeng {
    // customers!: any[];
    customers = signal<any[]>([])

    selectedCustomers!: any;

    constructor() {}

    // ngOnInit() {

    //     this.customerService.getCustomersSmall().then((data) => (this.customers = data));
    // }
    ngOnInit() {
      // this.customers.update(clients)
      this.customers.set(clients)
        // this.customerService.getCustomersSmall().then((data) => (this.customers = data));
    }

    getSeverity(status: string) {
        switch (status) {
            case 'unqualified':
                return 'danger';

            case 'qualified':
                return 'success';

            case 'new':
                return 'info';

            case 'negotiation':
                return 'warn';

            case 'renewal':
                return null;
            default: return 'unknown'; // ✅ ahora todos los caminos retornan
        }
        
    }
}




// import { Component, OnInit } from '@angular/core';
// import { Customer } from '@/domain/customer';
// import { CustomerService } from '@/service/customerservice';
// import { TableModule } from 'primeng/table';
// import { HttpClientModule } from '@angular/common/http';
// import { InputTextModule } from 'primeng/inputtext';
// import { IconField } from 'primeng/iconfield';
// import { InputIcon } from 'primeng/inputicon';
// import { Tag } from 'primeng/tag';

// @Component({
//     selector: 'table-stateful-demo',
//     templateUrl: 'table-stateful-demo.html',
//     standalone: true,
//     imports: [TableModule, HttpClientModule, InputTextModule, Tag, IconField, InputIcon],
//     providers: [CustomerService]
// })
// export class TableStatefulDemo implements OnInit{
//     customers!: Customer[];

//     selectedCustomers!: Customer;

//     constructor(private customerService: CustomerService) {}

//     ngOnInit() {
//         this.customerService.getCustomersSmall().then((data) => (this.customers = data));
//     }

//     getSeverity(status: string) {
//         switch (status) {
//             case 'unqualified':
//                 return 'danger';

//             case 'qualified':
//                 return 'success';

//             case 'new':
//                 return 'info';

//             case 'negotiation':
//                 return 'warn';

//             case 'renewal':
//                 return null;
//         }
//     }
// }