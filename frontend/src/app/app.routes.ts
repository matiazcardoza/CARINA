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
import { PhysicalBincard } from './features/private/physical-bincard/physical-bincard';
import { InventoryReports } from './features/private/inventory-reports/inventory-reports';
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
import { NoPermissions } from './features/private/no-permissions/no-permissions';

// Importar el guard genérico
import { PermissionGuard } from './services/AuthService/permission.guard';

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
        canActivate: [authGuard],
        children: [
            {
                path: '',
                redirectTo: 'home',
                pathMatch: 'full'
            },
            // Dashboard - Requiere permiso access_dashboard
            {
                path: 'home',
                component: Dashboards,
                canActivate: [PermissionGuard],
                data: { 
                    permissions: ['access_dashboard'],
                    redirectTo: '/private/no-permissions'
                }
            },
            // Daily Work Log - Requiere permiso access_work_log
            {
                path: 'daily-work-log',
                canActivate: [PermissionGuard],
                data: { 
                    permissions: ['access_work_log'],
                    redirectTo: '/private/no-permissions'
                },
                children: [
                    {
                        path: '',
                        component: DailyWorkLog
                    },
                    // Work Log ID específico - Requiere permisos más específicos
                    {
                        path: 'daily-work-log-id/:id',
                        component: DailyWorkLogId,
                        canActivate: [PermissionGuard],
                        data: { 
                            permissions: ['access_work_log_id', 'edit_work_log_id'],
                            checkType: 'any', // Puede tener cualquiera de estos permisos
                            redirectTo: '/private/daily-work-log'
                        }
                    }
                ]
            },
            // Mechanical Equipment - Requiere permiso access_equipo_mecanico
            {
                path: 'mechanical_equipment',
                component: MechanicalEquipment,
                canActivate: [PermissionGuard],
                data: { 
                    permissions: ['access_equipo_mecanico'],
                    redirectTo: '/private/no-permissions'
                }
            },
            // Users - Solo SuperAdministrador
            {
                path: 'users',
                component: Users,
                canActivate: [PermissionGuard],
                data: { 
                    roles: ['SuperAdministrador_pd'],
                    redirectTo: '/private/no-permissions'
                }
            },
            // Roles - Solo SuperAdministrador
            {
                path: 'roles',
                component: Roles,
                canActivate: [PermissionGuard],
                data: { 
                    roles: ['SuperAdministrador_pd'],
                    redirectTo: '/private/no-permissions'
                }
            },
            // Reports - Requiere permiso access_reportes
            {
                path: 'reports',
                component: Reports,
                canActivate: [PermissionGuard],
                data: { 
                    permissions: ['access_reportes'],
                    redirectTo: '/private/no-permissions'
                }
            },
            // Página de sin permisos
            {
                path: 'no-permissions',
                component: NoPermissions
            },
            // Digital Signature Tray - Sin permisos específicos por ahora
            {
                path: 'digital-signature-tray',
                component: DigitalSignatureTray
                // Agregar guard cuando definas el permiso específico:
                // canActivate: [PermissionGuard],
                // data: { permissions: ['access_signature_tray'] }
            },
            // Kardex Management - Sección de almacén (agregar permisos cuando estén definidos)
            {
                path: 'kardex-management',
                children: [
                    {
                        path: '',
                        component: KardexManagement
                        // Agregar cuando definas permisos de almacén:
                        // canActivate: [PermissionGuard],
                        // data: { permissions: ['access_kardex'] }
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
            // Digital Signatures - Firmas de reportes
            {
                path: 'digital-signatures',
                children: [
                    {
                        path: '',
                        component: SignaturesMovementReports
                        // Agregar cuando definas permisos específicos:
                        // canActivate: [PermissionGuard],
                        // data: { permissions: ['access_digital_signatures'] }
                    }
                ]
            },
            // Test Requests
            {
                path: 'test-requests',
                component: TestResquests
                // Agregar guard si es necesario
            },
            // Physical Bincard
            {
                path: 'physical-bincard',
                children: [
                    {
                        path: '',
                        component: PhysicalBincard
                    },
                    {
                        path: ':bincardId',
                        component: Products
                    },
                    {
                        path: ':bincardId/products',
                        component: Products
                    },
                    {
                        path: ':bincardId/products/:productId',
                        component: Products
                    },
                    {
                        path: ':bincardId/products/:productId/edit',
                        component: Products
                    }
                ]
            },
            // Fuel Vouchers - Vales de combustible
            {
                path: 'fuel-vaucher',
                component: FuelVouchers
                // Agregar cuando definas permisos de vales de transporte:
                // canActivate: [PermissionGuard],
                // data: { permissions: ['access_fuel_vouchers'] }
            }
        ]
    },
    // Draft section para pruebas
    {
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
            }
        ]
    }
    // {
    //     path: '**',
    //     component: NotFound
    // }
];