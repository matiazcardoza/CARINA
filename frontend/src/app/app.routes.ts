import { Routes } from '@angular/router';
import { Dashboard } from './layouts/dashboard/dashboard';

import { Login } from './features/public/login/login';
import { Register } from './features/public/register/register';

import { Home } from './features/private/home/home';
import { MyFirtsFeature } from './features/private/my-firts-feature/my-firts-feature';
import { MySecondFeature } from './features/private/my-second-feature/my-second-feature';
import { MyThirdFeature } from './features/private/my-third-feature/my-third-feature';
import { Conformity } from './features/private/conformity/conformity';
import { MyFirstComponent } from './shared/draft/my-first-component/my-first-component';
import { DailyWorkLog } from './features/private/daily-work-log/daily-work-log';
import { FuelControl } from './features/private/fuel-control/fuel-control';
import { EvidenceManagement } from './features/private/evidence-management/evidence-management';
import { DigitalSignatureWorkflow } from './features/private/digital-signature-workflow/digital-signature-workflow';
import { ReportsAndDashboards } from './features/private/reports-and-dashboards/reports-and-dashboards';
import { ValuedKardex } from './features/private/valued-kardex/valued-kardex';
import { PhysicalBincard } from './features/private/physical-bincard/physical-bincard';
import { WarrantyControl } from './features/private/warranty-control/warranty-control';
import { InventoryReports } from './features/private/inventory-reports/inventory-reports';
import { ProjectBasedTraceability } from './features/private/project-based-traceability/project-based-traceability';


export const routes: Routes = [
    // Redirige la ruta raíz a la página de login
    {
        path: '',
        redirectTo: 'login',
        pathMatch: 'full'
    },
    // Ruta para el componente de login
    {
        path: 'login',
        component: Login
    },
    {
        path: 'register',
        component: Register
    },
    // Rutas protegidas que requieren autenticación (dashboard y sus hijos)
    {
        path: 'dashboard', 
        component: Dashboard,
        children: [
            {
                path: '',              
                redirectTo: 'home',    
                pathMatch: 'full'       
            },
            {
                path: 'home',
                component: Home
            },
            {
                path: 'daily-parts',
                children: [
                    {
                        path: '',              
                        redirectTo: 'daily-work-log',    
                        pathMatch: 'full'       
                    },
                    {
                        path: 'daily-work-log',
                        component: DailyWorkLog
                    },
                    {
                        path: 'fuel-control',
                        component: FuelControl
                    },
                    {
                        path: 'evidence-management',
                        component: EvidenceManagement
                    },
                    {
                        path: 'digital-signature-workflow',
                        component: DigitalSignatureWorkflow
                    },
                    {
                        path: 'reports-and-dashboards',
                        component: ReportsAndDashboards
                    },
                ]
            },
            {
                path: 'warehouse',
                children: [
                    {
                        path: '',              
                        redirectTo: 'valued-kardex',    
                        pathMatch: 'full'       
                    },
                    {
                        path: 'valued-kardex',
                        component: ValuedKardex
                    },
                    {
                        path: 'physical-bincard',
                        component: PhysicalBincard
                    },
                    {
                        path: 'warranty-control',
                        component: WarrantyControl
                    },
                    {
                        path: 'inventory-reports',
                        component: InventoryReports
                    },
                    {
                        path: 'project-based-traveability',
                        component: ProjectBasedTraceability
                    },

                ]
            },
            {
                path: 'first-feature',
                component: MyFirtsFeature
            },
            {
                path: 'second-feature',
                component: MySecondFeature
            },
            {
                path: 'third-feature',
                component: MyThirdFeature
            },
            {
                path: 'conformity',
                component: Conformity
            }
        ]
    },
    {
        // This section is to prove new functionalities
        path: 'draft',
        children:[
            {
                path: '',              
                redirectTo: 'how-send-values',    
                pathMatch: 'full'       
            },
            {
                path: 'how-send-values',
                component: MyFirstComponent
            },
            {
                path: 'first-feature',
                component: MyFirtsFeature
            },
        ]
    },
    {
        path: '**', 
        redirectTo: ''
    }
];

/*export const routes: Routes = [
    { path: 'login', component: LoginComponent },
    {
        path: '', 
        redirectTo: 'dashboard', 
        pathMatch: 'full'
    },
    
    {
        path: 'dashboard', 
        component: Dashboard,
        children: [
            {
                path: '',              
                redirectTo: 'home',    
                pathMatch: 'full'       
            },
            {
                path: 'home',
                component: Home
            },
            {
                path: 'daily-parts',
                children: [
                    {
                        path: '',              
                        redirectTo: 'daily-work-log',    
                        pathMatch: 'full'       
                    },
                    {
                        path: 'daily-work-log',
                        component: DailyWorkLog
                    },
                    {
                        path: 'fuel-control',
                        component: FuelControl
                    },
                    {
                        path: 'evidence-management',
                        component: EvidenceManagement
                    },
                    {
                        path: 'digital-signature-workflow',
                        component: DigitalSignatureWorkflow
                    },
                    {
                        path: 'reports-and-dashboards',
                        component: ReportsAndDashboards
                    },
                ]
            },
            {
                path: 'warehouse',
                children: [
                    {
                        path: '',              
                        redirectTo: 'valued-kardex',    
                        pathMatch: 'full'       
                    },
                    {
                        path: 'valued-kardex',
                        component: ValuedKardex
                    },
                    {
                        path: 'physical-bincard',
                        component: PhysicalBincard
                    },
                    {
                        path: 'warranty-control',
                        component: WarrantyControl
                    },
                    {
                        path: 'inventory-reports',
                        component: InventoryReports
                    },
                    {
                        path: 'project-based-traveability',
                        component: ProjectBasedTraceability
                    },

                ]
            },
            {
                path: 'first-feature',
                component: MyFirtsFeature
            },
            {
                path: 'second-feature',
                component: MySecondFeature
            },
            {
                path: 'third-feature',
                component: MyThirdFeature
            },
            {
                path: 'conformity',
                component: Conformity
            }
        ]
    },
    {
        // This section is to prove new functionalities
        path: 'draft',
        children:[
            {
                path: '',              
                redirectTo: 'how-send-values',    
                pathMatch: 'full'       
            },
            {
                path: 'how-send-values',
                component: MyFirstComponent
            },
            {
                path: 'first-feature',
                component: MyFirtsFeature
            },
        ]
    },
    {
        path: '**', 
        redirectTo: ''
    }
];*/
