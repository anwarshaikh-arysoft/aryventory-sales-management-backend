import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { type PropsWithChildren } from 'react';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Profile',
        href: '/settings/profile',
        icon: null,
    },
    {
        title: 'Password',
        href: '/settings/password',
        icon: null,
    },
    {
        title: 'Appearance',
        href: '/settings/appearance',
        icon: null,
    },
];

const adminSidebarNavItems: NavItem[] = [
    {
        title: 'Business Types',
        href: '/admin/settings/business-types',
        icon: null,
    },
    {
        title: 'Current Systems',
        href: '/admin/settings/current-systems',
        icon: null,
    },
    {
        title: 'Groups',
        href: '/admin/settings/groups',
        icon: null,
    },
    {
        title: 'Lead Status',
        href: '/admin/settings/lead-status',
        icon: null,
    },
    {
        title: 'Plans',
        href: '/admin/settings/plans',
        icon: null,
    },
    {
        title: 'Preferences',
        href: '/admin/settings/preferences',
        icon: null,
    },
    {
        title: 'Roles',
        href: '/admin/settings/roles',
        icon: null,
    },
    {
        title: 'Targets',
        href: '/admin/settings/targets',
        icon: null,
    },
];

export default function SettingsLayout({ children }: PropsWithChildren) {
    // When server-side rendering, we only render the layout on the client...
    if (typeof window === 'undefined') {
        return null;
    }

    const currentPath = window.location.pathname;
    const isAdminSettings = currentPath.startsWith('/admin/settings');
    const navItems = isAdminSettings ? adminSidebarNavItems : sidebarNavItems;
    const title = isAdminSettings ? 'Admin Settings' : 'Settings';
    const description = isAdminSettings ? 'Manage system configuration and data' : 'Manage your profile and account settings';

    return (
        <div className="px-4 py-6">
            <Heading title={title} description={description} />

            <div className="flex flex-col space-y-8 lg:flex-row lg:space-y-0 lg:space-x-12 mt-6">
                <aside className="w-full max-w-xl lg:w-48">
                    <nav className="flex flex-col space-y-1 space-x-0">
                        {navItems.map((item, index) => (
                            <Button
                                key={`${item.href}-${index}`}
                                size="sm"
                                variant="ghost"
                                asChild
                                className={cn('w-full justify-start', {
                                    'bg-muted': currentPath === item.href,
                                })}
                            >
                                <Link href={item.href} prefetch>
                                    {item.title}
                                </Link>
                            </Button>
                        ))}
                    </nav>
                </aside>

                <Separator className="my-6 md:hidden" />

                <div className="flex-1 md:max-w-full">
                    <section className={cn("space-y-12", isAdminSettings ? "max-w-6xl" : "max-w-xl")}>{children}</section>
                </div>
            </div>
        </div>
    );
}
