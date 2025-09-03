import { useState, useEffect } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { 
    Users, 
    MapPin, 
    TrendingUp, 
    Target, 
    Calendar,
    Filter,
    Download,
    RefreshCw,
    Clock,
    CheckCircle,
    DollarSign,
    BarChart3
} from 'lucide-react';
import axios from 'axios';
import DashboardMap from '@/components/dashboard-map';
import { 
    RevenueChart, 
    PerformanceChart, 
    ShiftStatusChart, 
    DailyPerformanceChart, 
    RevenueTrendChart 
} from '@/components/dashboard-charts';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

interface ShiftLocation {
    id: number;
    user_name: string;
    designation: string;
    group: string;
    shift_date: string;
    shift_start: string;
    latitude: number;
    longitude: number;
    is_active: boolean;
}

interface DashboardOverview {
    shift_overview: {
        total_shifts_started: number;
        active_shifts: number;
        completed_shifts: number;
    };
    revenue_metrics: {
        by_executive: Array<{
            user_id: number;
            user_name: string;
            designation: string;
            group: string;
            completed_leads: number;
            estimated_revenue: number;
        }>;
        by_manager: Array<{
            manager_id: number;
            manager_name: string;
            completed_leads: number;
            estimated_revenue: number;
        }>;
        by_region: Array<{
            region_id: number;
            region_name: string;
            completed_leads: number;
            estimated_revenue: number;
        }>;
        total_completed_leads: number;
        total_estimated_revenue: number;
    };
    performance_metrics: Array<{
        user_id: number;
        user_name: string;
        designation: string;
        role: string;
        group: string;
        leads_created: number;
        leads_completed: number;
        meetings_conducted: number;
        shifts_worked: number;
        conversion_rate: number;
        target: number;
        target_achievement: number;
    }>;
}

export default function Dashboard() {
    const [overview, setOverview] = useState<DashboardOverview | null>(null);
    const [locations, setLocations] = useState<ShiftLocation[]>([]);
    const [loading, setLoading] = useState(true);
    const [startDate, setStartDate] = useState(new Date().toISOString().split('T')[0]);
    const [endDate, setEndDate] = useState(new Date().toISOString().split('T')[0]);
    const [selectedRegion, setSelectedRegion] = useState<string>('');
    const [selectedManager, setSelectedManager] = useState<string>('');
    const [revenueGroupBy, setRevenueGroupBy] = useState<string>('executive');

    // Fetch dashboard data
    const fetchDashboardData = async () => {
        try {
            setLoading(true);
            const params = new URLSearchParams({
                start_date: startDate,
                end_date: endDate,
                ...(selectedRegion && { region: selectedRegion }),
                ...(selectedManager && { manager_id: selectedManager }),
            });

            const [overviewRes, locationsRes] = await Promise.all([
                axios.get(`/api/dashboard/overview?${params}`),
                axios.get(`/api/dashboard/shift-locations?${params}`)
            ]);

            setOverview(overviewRes.data);
            setLocations(locationsRes.data.locations);
        } catch (error) {
            console.error('Error fetching dashboard data:', error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchDashboardData();
    }, [startDate, endDate, selectedRegion, selectedManager]);

    // Set default date range to today
    useEffect(() => {
        const today = new Date().toISOString().split('T')[0];
        setStartDate(today);
        setEndDate(today);
    }, []);

    const applyQuickFilter = (days: number) => {
        const end = new Date();
        const start = new Date();
        start.setDate(start.getDate() - days);
        
        setStartDate(start.toISOString().split('T')[0]);
        setEndDate(end.toISOString().split('T')[0]);
    };

    if (loading) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Dashboard" />
                <div className="flex items-center justify-center h-64">
                    <RefreshCw className="animate-spin h-8 w-8" />
                    <span className="ml-2">Loading dashboard...</span>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4 overflow-x-auto">
                
                {/* Header with Filters */}
                <div className="flex flex-col md:flex-row gap-4 items-start md:items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">Sales Dashboard</h1>
                        <p className="text-gray-600">Real-time insights into your sales operations</p>
                    </div>
                    
                    <div className="flex flex-wrap gap-2 items-center">
                        {/* Quick Filter Buttons */}
                        <Button 
                            variant="outline" 
                            size="sm" 
                            onClick={() => applyQuickFilter(0)}
                        >
                            Today
                        </Button>
                        <Button 
                            variant="outline" 
                            size="sm" 
                            onClick={() => applyQuickFilter(7)}
                        >
                            Last 7 Days
                        </Button>
                        <Button 
                            variant="outline" 
                            size="sm" 
                            onClick={() => applyQuickFilter(30)}
                        >
                            Last 30 Days
                        </Button>
                        
                        {/* Date Range Inputs */}
                        <Input
                            type="date"
                            value={startDate}
                            onChange={(e) => setStartDate(e.target.value)}
                            className="w-auto"
                        />
                        <span className="text-gray-500">to</span>
                        <Input
                            type="date"
                            value={endDate}
                            onChange={(e) => setEndDate(e.target.value)}
                            className="w-auto"
                        />
                        
                        <Button onClick={fetchDashboardData} size="sm">
                            <RefreshCw className="h-4 w-4 mr-2" />
                            Refresh
                        </Button>
                    </div>
                </div>

                {/* Key Metrics Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Active Shifts</CardTitle>
                            <Clock className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-green-600">
                                {overview?.shift_overview.active_shifts || 0}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                of {overview?.shift_overview.total_shifts_started || 0} started today
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Completed Shifts</CardTitle>
                            <CheckCircle className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-blue-600">
                                {overview?.shift_overview.completed_shifts || 0}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                shifts completed
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Revenue</CardTitle>
                            <DollarSign className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-green-600">
                                ₹{(overview?.revenue_metrics.total_estimated_revenue || 0).toLocaleString()}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                from {overview?.revenue_metrics.total_completed_leads || 0} completed leads
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Active Users</CardTitle>
                            <Users className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-purple-600">
                                {locations.length}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                users with shift locations
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Map and Shift Status */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <MapPin className="h-5 w-5" />
                                Shift Locations
                            </CardTitle>
                            <CardDescription>
                                Real-time locations of staff who started their shifts
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <DashboardMap locations={locations} height="400px" />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Shift Status Overview</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ShiftStatusChart
                                totalShifts={overview?.shift_overview.total_shifts_started || 0}
                                activeShifts={overview?.shift_overview.active_shifts || 0}
                                completedShifts={overview?.shift_overview.completed_shifts || 0}
                                height={300}
                            />
                        </CardContent>
                    </Card>
                </div>

                {/* Revenue Charts */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <TrendingUp className="h-5 w-5" />
                                Revenue by Sales Executive
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <RevenueChart
                                data={overview?.revenue_metrics.by_executive.map(item => ({
                                    name: item.user_name,
                                    completed_leads: item.completed_leads,
                                    estimated_revenue: item.estimated_revenue,
                                })) || []}
                                title=""
                                height={300}
                            />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <BarChart3 className="h-5 w-5" />
                                Revenue by Region
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <RevenueChart
                                data={overview?.revenue_metrics.by_region.map(item => ({
                                    name: item.region_name,
                                    completed_leads: item.completed_leads,
                                    estimated_revenue: item.estimated_revenue,
                                })) || []}
                                title=""
                                height={300}
                            />
                        </CardContent>
                    </Card>
                </div>

                {/* Performance Metrics */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Target className="h-5 w-5" />
                            User Performance Overview
                        </CardTitle>
                        <CardDescription>
                            Performance metrics for all sales staff
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <PerformanceChart
                            data={overview?.performance_metrics || []}
                            height={400}
                        />
                    </CardContent>
                </Card>

                {/* Performance Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Detailed Performance Metrics</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b">
                                        <th className="text-left p-2">User</th>
                                        <th className="text-left p-2">Role</th>
                                        <th className="text-left p-2">Region</th>
                                        <th className="text-left p-2">Leads Created</th>
                                        <th className="text-left p-2">Leads Completed</th>
                                        <th className="text-left p-2">Meetings</th>
                                        <th className="text-left p-2">Shifts</th>
                                        <th className="text-left p-2">Conversion %</th>
                                        <th className="text-left p-2">Target Achievement</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {overview?.performance_metrics.map((user) => (
                                        <tr key={user.user_id} className="border-b hover:bg-gray-50">
                                            <td className="p-2 font-medium">{user.user_name}</td>
                                            <td className="p-2">{user.role}</td>
                                            <td className="p-2">{user.group}</td>
                                            <td className="p-2">{user.leads_created}</td>
                                            <td className="p-2">{user.leads_completed}</td>
                                            <td className="p-2">{user.meetings_conducted}</td>
                                            <td className="p-2">{user.shifts_worked}</td>
                                            <td className="p-2">
                                                <Badge variant={user.conversion_rate >= 20 ? "default" : "secondary"}>
                                                    {user.conversion_rate}%
                                                </Badge>
                                            </td>
                                            <td className="p-2">
                                                <Badge variant={user.target_achievement >= 80 ? "default" : "destructive"}>
                                                    {user.target_achievement}%
                                                </Badge>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
