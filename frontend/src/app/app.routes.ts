import { Routes } from '@angular/router';
import { Dashboard } from './layouts/dashboard/dashboard';

import { Login } from './features/public/login/login';

import { Home } from './features/private/home/home';
import { Conformity } from './features/private/conformity/conformity';
import { DailyWorkLog } from './features/private/daily-work-log/daily-work-log';
import { DailyWorkLogId } from './features/private/daily-work-log/daily-work-log-id/daily-work-log-id';

import { MechanicalEquipment } from './features/private/mechanical-equipment/mechanical-equipment';

import { Users } from './features/private/users/users';
import { Roles } from './features/private/roles/roles';

import { Reports } from './features/private/reports/reports';

import { DigitalSignatureTray } from './features/private/digital-signature-tray/digital-signature-tray';
import { Dashboards } from './features/private/dashboards/dashboards';
import { authGuard } from './services/AuthService/auth.guard';
import { publicGuard } from './services/AuthService/public.guard';
import { Sidebar } from './layouts/sidebar/sidebar';
import { NoPermissions } from './features/private/no-permissions/no-permissions';
import { PermissionGuard } from './services/AuthService/permission.guard';

export const routes: Routes = [
    {
        path: '',
        redirectTo: 'login',
        pathMatch: 'full'
    },
    {
        path: 'login',
        component: Login,
        canActivate: [publicGuard]
    },
    {
        path: 'carina',
        component: Dashboard,
        canActivate: [authGuard],
        children: [
            {
                path: '',
                redirectTo: 'dashboard',
                pathMatch: 'full'
            },
            {
                path: 'dashboard',
                component: Dashboards,
                canActivate: [PermissionGuard],
                data: {
                    permissions: ['access_dashboard'],
                    redirectTo: '/carina/no-permissions'
                }
            },
            {
                path: 'daily-work-log',
                canActivate: [PermissionGuard],
                data: {
                    permissions: ['access_work_log'],
                    redirectTo: '/carina/no-permissions'
                },
                children: [
                    {
                        path: '',
                        component: DailyWorkLog
                    },
                    {
                        path: 'daily-work-log-id/:id/:state',
                        component: DailyWorkLogId,
                        canActivate: [PermissionGuard],
                        data: {
                            permissions: ['access_work_log_id', 'edit_work_log_id'],
                            checkType: 'any',
                            redirectTo: '/carina/daily-work-log'
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
                    redirectTo: '/carina/no-permissions'
                }
            },
            {
                path: 'users',
                component: Users,
                canActivate: [PermissionGuard],
                data: {
                    roles: ['SuperAdministrador_pd'],
                    redirectTo: '/carina/no-permissions'
                }
            },
            {
                path: 'roles',
                component: Roles,
                canActivate: [PermissionGuard],
                data: {
                    roles: ['SuperAdministrador_pd'],
                    redirectTo: '/carina/no-permissions'
                }
            },
            {
                path: 'reports',
                component: Reports,
                canActivate: [PermissionGuard],
                data: {
                    permissions: ['access_reportes'],
                    redirectTo: '/carina/no-permissions'
                }
            },
            {
                path: 'no-permissions',
                component: NoPermissions
            },
            {
                path: 'digital-signature-tray',
                component: DigitalSignatureTray,
                canActivate: [PermissionGuard],
                data: {
                    permissions: ['access_tray_signature'],
                    redirectTo: '/carina/no-permissions'
                }
            },
        ]
    },
];
