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
import { DailyWorkLogId } from './features/private/daily-work-log/daily-work-log-id/daily-work-log-id';

import { MechanicalEquipment } from './features/private/mechanical-equipment/mechanical-equipment';

import { FuelControl } from './features/private/fuel-control/fuel-control';
import { EvidenceManagement } from './features/private/evidence-management/evidence-management';
import { DigitalSignatureWorkflow } from './features/private/digital-signature-workflow/digital-signature-workflow';
import { ReportsAndDashboards } from './features/private/reports-and-dashboards/reports-and-dashboards';
import { ValuedKardex } from './features/private/valued-kardex/valued-kardex';
import { PhysicalBincard } from './features/private/physical-bincard/physical-bincard';
import { WarrantyControl } from './features/private/warranty-control/warranty-control';
import { InventoryReports } from './features/private/inventory-reports/inventory-reports';
import { ProjectBasedTraceability } from './features/private/project-based-traceability/project-based-traceability';
import { NotFound } from './layouts/not-found/not-found';
import { authGuard } from './services/AuthService/auth.guard';
import { publicGuard } from './services/AuthService/public.guard';
import { Products } from './features/private/physical-bincard/components/products/products';
import { UploadFile } from './shared/draft/upload-file/upload-file';
import { ComponentTesting } from './shared/draft/component-testing/component-testing';
import { KardexManagement } from './features/private/kardex-management/kardex-management';
import { DigitalSignature } from './shared/draft/digital-signature/digital-signature';
import { Sidebar } from './layouts/sidebar/sidebar';
import { SignaturesMovementReports } from './features/private/signatures-movement-reports/signatures-movement-reports';
import { TestResquests } from './shared/draft/test-resquests/test-resquests';
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
        component: Login,
        canActivate: [publicGuard]
    },
    {
        path: 'register',
        component: Register,
        canActivate: [publicGuard]
    },
    // Rutas protegidas que requieren autenticación (dashboard y sus hijos)
    {
        path: 'carina', 
        component: Dashboard,
        canActivate: [authGuard], // <--- ¡Esta es la línea clave que añadimos!
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
                path: 'daily-work-log', 
                children: [
                    { 
                    path: '', 
                    component: DailyWorkLog 
                    },
                    { 
                    path: 'daily-work-log-id/:id', 
                    component: DailyWorkLogId 
                    }
                ]
            },
            {
                path: 'mechanical_equipment',
                component: MechanicalEquipment
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
            {
                path: 'valued-kardex',
                component: ValuedKardex
            },
            {
                path: 'kardex-management',
                children: [
                    {
                        path: '',   
                        component: KardexManagement,
                    }
                ]
            },
            {
                path: 'digital-signatures',
                children: [
                    {
                        path: '',   
                        component: SignaturesMovementReports,
                    },
                ]
            },
            {
                path: 'test-requests',
                component: TestResquests,
            },
            {
                path: 'physical-bincard',
                children: [
                    {
                        path: '',   
                        component: PhysicalBincard,
                    },
                    {
                        path: ':bincardId',
                        component: Products,
                    },
                    {
                        path: ':bincardId/products',
                        component: Products,
                    },
                    {
                        path: ':bincardId/products/:productId',
                        component: Products,
                    },
                    {
                        path: ':bincardId/products/:productId/edit',
                        component: Products,
                    },

                ]
                
            },
            // {
            //     // muestra los productos del bicard fisico con el id "id"
            //     path: 'physical-bincard/:id/products',          
            //     component: Products,
            // },
            {
                path: 'warranty-control',
                component: WarrantyControl
            },
            {
                path: 'inventory-reports',
                component: SignaturesMovementReports
            },
            {
                path: 'project-based-traveability',
                component: ProjectBasedTraceability
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
            {
                path: 'upload-file',
                component: UploadFile
            },
            {
                path: 'component-testing',
                component: ComponentTesting
            },
            {
                path: 'digital-signature',
                component: DigitalSignature
            },
            {
                path: 'sidebar-exmple',
                component: Sidebar
            },
        ]
    },
    // {
    //     path: '**',  
    //     component: NotFound
    // }
];