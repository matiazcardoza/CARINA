import { Routes } from '@angular/router';
import { Dashboard } from './layouts/dashboard/dashboard';

import { Login } from './features/public/login/login';

import { Home } from './features/private/home/home';
import { Conformity } from './features/private/conformity/conformity';
import { MyFirstComponent } from './shared/draft/my-first-component/my-first-component';
import { DailyWorkLog } from './features/private/daily-work-log/daily-work-log';
import { DailyWorkLogId } from './features/private/daily-work-log/daily-work-log-id/daily-work-log-id';

import { MechanicalEquipment } from './features/private/mechanical-equipment/mechanical-equipment';

import { Users } from './features/private/users/users';
import { Roles } from './features/private/roles/roles';

import { Reports } from './features/private/reports/reports';

import { DigitalSignatureTray } from './features/private/digital-signature-tray/digital-signature-tray';
import { Dashboards } from './features/private/dashboards/dashboards';
// import { ValuedKardex } from './features/private/valued-kardex/valued-kardex';
import { PhysicalBincard } from './features/private/physical-bincard/physical-bincard';
import { InventoryReports } from './features/private/inventory-reports/inventory-reports';
// import { ProjectBasedTraceability } from './features/private/project-based-traceability/project-based-traceability';
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
import { FuelVouchers } from './features/private/fuel-vouchers/fuel-vouchers';
import { WhmKardexManagement } from './features/private/whm-kardex-management/whm-kardex-management';
import { RenderingTest } from './shared/draft/rendering-test/rendering-test';
import { WhmUserManagement } from './features/private/whm-user-management/whm-user-management';
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
    // Rutas protegidas que requieren autenticación (dashboard y sus hijos)
    {
        path: 'private',
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
                component: Dashboards
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
                path: 'users',
                component: Users
            },
            {
                path: 'roles',
                component: Roles
            },
            {
                path: 'reports',
                component: Reports
            },
            {
                path: 'digital-signature-tray',
                component: DigitalSignatureTray
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
                path: 'whm-user-management',
                component: WhmUserManagement,
            },
            {
                path: 'whm-kardex-management',
                component: WhmKardexManagement,
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
            {
                path: 'fuel-vaucher',
                component: FuelVouchers
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
            {
                path: 'rendering-test',
                component: RenderingTest
            },
        ]
    },
    // {
    //     path: '**',
    //     component: NotFound
    // }
];
