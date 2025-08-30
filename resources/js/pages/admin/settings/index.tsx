import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Link } from '@inertiajs/react';
import { 
    Building2, 
    Settings, 
    Users, 
    Target, 
    Package, 
    Heart, 
    ShieldCheck, 
    Activity 
} from 'lucide-react';

const settingsCards = [
    {
        title: 'Business Types',
        description: 'Manage business types for lead categorization',
        href: '/admin/settings/business-types',
        icon: Building2,
        color: 'text-blue-600',
    },
    {
        title: 'Current Systems',
        description: 'Manage current systems for lead tracking',
        href: '/admin/settings/current-systems',
        icon: Settings,
        color: 'text-green-600',
    },
    {
        title: 'Groups',
        description: 'Manage user groups and teams',
        href: '/admin/settings/groups',
        icon: Users,
        color: 'text-purple-600',
    },
    {
        title: 'Lead Status',
        description: 'Manage lead status options for lead tracking',
        href: '/admin/settings/lead-status',
        icon: Activity,
        color: 'text-orange-600',
    },
    {
        title: 'Plans',
        description: 'Manage subscription plans and pricing',
        href: '/admin/settings/plans',
        icon: Package,
        color: 'text-indigo-600',
    },
    {
        title: 'Preferences',
        description: 'Manage user preferences and settings',
        href: '/admin/settings/preferences',
        icon: Heart,
        color: 'text-pink-600',
    },
    {
        title: 'Roles',
        description: 'Manage user roles and permissions',
        href: '/admin/settings/roles',
        icon: ShieldCheck,
        color: 'text-red-600',
    },
    {
        title: 'Targets',
        description: 'Manage user targets and performance goals',
        href: '/admin/settings/targets',
        icon: Target,
        color: 'text-teal-600',
    },
];

export default function AdminSettingsIndex() {
    return (
        <AppLayout>
            <SettingsLayout>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {settingsCards.map((setting) => {
                        const IconComponent = setting.icon;
                        return (
                            <Link key={setting.href} href={setting.href} className="block">
                                <Card className="h-full hover:shadow-md transition-shadow duration-200 cursor-pointer">
                                    <CardHeader className="pb-3">
                                        <div className="flex items-center space-x-3">
                                            <div className={`p-2 rounded-lg bg-gray-100 ${setting.color}`}>
                                                <IconComponent className="h-6 w-6" />
                                            </div>
                                            <div>
                                                <CardTitle className="text-lg">{setting.title}</CardTitle>
                                            </div>
                                        </div>
                                    </CardHeader>
                                    <CardContent>
                                        <CardDescription className="text-sm">
                                            {setting.description}
                                        </CardDescription>
                                    </CardContent>
                                </Card>
                            </Link>
                        );
                    })}
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
