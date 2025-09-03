import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Users, CheckCircle, ExternalLink } from "lucide-react";
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import Heading from '@/components/heading';
import AppLayout from '@/layouts/app-layout';
import { type PaginatedResponse, type Lead, type LeadFormOptions } from '@/types';
import { Edit2, Plus, Search, Trash2, Star, Phone, Mail, MapPin } from 'lucide-react';
import { useState, useEffect } from 'react';
import axios from 'axios';
import dayjs from "dayjs";
import isoWeek from "dayjs/plugin/isoWeek";

import LeadModal from '@/components/lead-modal';
import { start } from 'repl';

dayjs.extend(isoWeek);

interface LeadsPageProps {
    // These will be passed from the backend
}

export default function Leads(props: LeadsPageProps) {
    const [leads, setLeads] = useState<PaginatedResponse<Lead> | null>(null);
    const [formOptions, setFormOptions] = useState<LeadFormOptions | null>(null);
    const [loading, setLoading] = useState(true);
    const [searchTerm, setSearchTerm] = useState('');
    const [selectedStatus, setSelectedStatus] = useState<string>('');
    const [selectedBusinessType, setSelectedBusinessType] = useState<string>('');
    const [selectedAssignedTo, setSelectedAssignedTo] = useState<string>('');
    const [currentPage, setCurrentPage] = useState(1);
    const [perPage, setPerPage] = useState(10);

    // Modal state
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingLead, setEditingLead] = useState<Lead | null>(null);

    // Lead Count state
    const [totalLeads, setTotalLeads] = useState(0);
    const [completedLeads, setCompletedLeads] = useState(0);

    // Dates states
    const [startDate, setStartDate] = useState("");
    const [endDate, setEndDate] = useState("");
    const [filterType, setFilterType] = useState("today");

    // Apply quick filter with strict filter type
    type QuickFilterType = "today" | "yesterday" | "this_week" | "this_month" | "last_3_months";

    const applyQuickFilter = (type: QuickFilterType) => {
        let start, end;
        const today = dayjs();

        switch (type) {
            case "today":
                start = today.format("YYYY-MM-DD");
                end = today.format("YYYY-MM-DD");
                break;
            case "yesterday":
                start = today.subtract(1, "day").format("YYYY-MM-DD");
                end = start;
                break;
            case "this_week":
                start = today.startOf("isoWeek").format("YYYY-MM-DD"); // Monday
                end = today.endOf("isoWeek").format("YYYY-MM-DD"); // Sunday
                break;
            case "this_month":
                start = today.startOf("month").format("YYYY-MM-DD");
                end = today.endOf("month").format("YYYY-MM-DD");
                break;
            case "last_3_months": // New case for past three months
                start = today.subtract(3, "month").startOf("month").format("YYYY-MM-DD");
                end = today.endOf("month").format("YYYY-MM-DD");
                break;
            default:
                return;
        }

        setStartDate(start);
        setEndDate(end);
        setFilterType(type);
        fetchLeadCounts(start, end);
    };

    const applyCustomFilter = () => {
        if (startDate && endDate) {
            setFilterType("custom");
            fetchLeadCounts(startDate, endDate);
        }
    };

    const fetchLeadCounts = async (start: string, end: string) => {
        try {
            const res = await axios.get("/api/leads-count", {
                params: { start_date: start, end_date: end },
            });
            setTotalLeads(res.data.total_leads);
            setCompletedLeads(res.data.completed_leads);
        } catch (error) {
            console.error("Error fetching leads count:", error);
        }
    };

    const fetchLeads = async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams({
                page: currentPage.toString(),
                per_page: perPage.toString(),
            });

            if (searchTerm) {
                params.append("shop_name", searchTerm);
            }
            if (selectedStatus) {
                params.append("lead_status", selectedStatus);
            }
            if (selectedBusinessType) {
                params.append("business_type", selectedBusinessType);
            }
            if (selectedAssignedTo) {
                params.append("assigned_to", selectedAssignedTo);
            }
            if (startDate) {
                params.append("start_date", startDate);
            }
            if (endDate) {
                params.append("end_date", endDate);
            }

            const response = await axios.get(`/api/leads?${params.toString()}`);
            console.log(`/api/leads?${params.toString()}`);
            setLeads(response.data);            
        } catch (error) {
            console.error("Error fetching leads:", error);
        }
        setLoading(false);
    };


    const fetchFormOptions = async () => {
        try {
            const response = await axios.get('/api/leads-form-options');
            setFormOptions(response.data);
        } catch (error) {
            console.error('Error fetching form options:', error);
        }
    };

    useEffect(() => {
        fetchFormOptions();
    }, []);

    useEffect(() => {
        fetchLeads();
        console.log('Fetched Leads' + leads);
    }, [currentPage, perPage, searchTerm, selectedStatus, selectedBusinessType, selectedAssignedTo, startDate, endDate]);

    useEffect(() => {
        applyQuickFilter("last_3_months");
    }, []);

    const handleSearch = (value: string) => {
        setSearchTerm(value);
        setCurrentPage(1);
    };

    const handleStatusFilter = (value: string) => {
        setSelectedStatus(value === 'all' ? '' : value);
        setCurrentPage(1);
    };

    const handleBusinessTypeFilter = (value: string) => {
        setSelectedBusinessType(value === 'all' ? '' : value);
        setCurrentPage(1);
    };

    const handleAssignedToFilter = (value: string) => {
        setSelectedAssignedTo(value === 'all' ? '' : value);
        setCurrentPage(1);
    };

    const handleAddLead = () => {
        setEditingLead(null);
        setIsModalOpen(true);
    };

    const handleEditLead = (lead: Lead) => {
        setEditingLead(lead);
        setIsModalOpen(true);
    };

    const handleDeleteLead = async (leadId: number) => {
        if (confirm('Are you sure you want to delete this lead?')) {
            try {
                await axios.delete(`/api/leads/${leadId}`);
                fetchLeads(); // Refresh the list
            } catch (error) {
                console.error('Error deleting lead:', error);
            }
        }
    };

    const handleModalClose = () => {
        setIsModalOpen(false);
        setEditingLead(null);
    };

    const handleLeadSaved = () => {
        fetchLeads(); // Refresh the list
        handleModalClose();
    };

    const generatePaginationItems = () => {
        if (!leads) return [];

        const items = [];
        const totalPages = leads.last_page;
        const current = leads.current_page;

        const start = Math.max(1, current - 2);
        const end = Math.min(totalPages, start + 4);

        for (let i = start; i <= end; i++) {
            items.push(i);
        }

        return items;
    };

    const getStatusBadgeVariant = (status: string) => {
        switch (status?.toLowerCase()) {
            case 'hot': return 'destructive';
            case 'warm': return 'default';
            case 'cold': return 'secondary';
            default: return 'outline';
        }
    };

    const renderRating = (rating: number | undefined) => {
        if (!rating) return '-';
        return (
            <div className="flex items-center gap-1">
                {[...Array(5)].map((_, i) => (
                    <Star
                        key={i}
                        className={`h-3 w-3 ${i < rating ? 'fill-yellow-400 text-yellow-400' : 'text-gray-300'
                            }`}
                    />
                ))}
                <span className="text-xs ml-1">({rating})</span>
            </div>
        );
    };

    const formatDate = (dateString: string | undefined) => {
        if (!dateString) return '-';
        return new Date(dateString).toLocaleDateString();
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Leads', href: '/leads' }
            ]}
        >
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">

                {/* Cards */}
                <div className="grid auto-rows-min gap-4 md:grid-cols-2">
                    {/* Total Leads */}
                    <Card className="flex items-center flex-row p-4 hover:shadow-lg transition-shadow duration-200">
                        <div className="bg-blue-100 text-blue-600 p-3 rounded-full">
                            <Users size={24} />
                        </div>
                        <CardHeader className="ml-4 p-0">
                            <CardDescription className="text-gray-500">Total Leads</CardDescription>
                            <CardTitle className="text-2xl font-bold">{totalLeads}</CardTitle>
                        </CardHeader>
                    </Card>

                    {/* Completed Leads */}
                    <Card className="flex items-center flex-row p-4 hover:shadow-lg transition-shadow duration-200">
                        <div className="bg-green-100 text-green-600 p-3 rounded-full">
                            <CheckCircle size={24} />
                        </div>
                        <CardHeader className="ml-4 p-0">
                            <CardDescription className="text-gray-500">Completed Leads</CardDescription>
                            <CardTitle className="text-2xl font-bold">{completedLeads}</CardTitle>
                        </CardHeader>
                    </Card>
                </div>

                <div className="flex items-center justify-between">
                    <Heading title="Leads Management" />
                    <Button onClick={handleAddLead} className="flex items-center gap-2">
                        <Plus className="h-4 w-4" />
                        Add Lead
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>All Leads</CardTitle>
                        <CardDescription>
                            Manage leads, prospects, and customer information for your sales team.
                        </CardDescription>
                    </CardHeader>

                    <CardContent className="space-y-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            {/* Date Range Picker */}
                            <div className="flex flex-col sm:flex-row sm:items-center gap-2">
                                <Input
                                    type="date"
                                    value={startDate}
                                    onChange={(e) => setStartDate(e.target.value)}
                                    className="w-full sm:w-40"
                                />
                                <span className="text-muted-foreground text-center sm:text-left">to</span>
                                <Input
                                    type="date"
                                    value={endDate}
                                    onChange={(e) => setEndDate(e.target.value)}
                                    className="w-full sm:w-40"
                                />
                                <Button
                                    onClick={applyCustomFilter}
                                    className="w-full sm:w-auto"
                                >
                                    Apply
                                </Button>
                            </div>

                            {/* Quick Filters */}
                            <div className="flex flex-wrap gap-2 justify-start md:justify-end">
                                {[
                                    { label: "Today", type: "today" },
                                    { label: "Yesterday", type: "yesterday" },
                                    { label: "This Week", type: "this_week" },
                                    { label: "This Month", type: "this_month" },
                                    { label: "Last 3 Months", type: "last_3_months" },
                                ].map(({ label, type }) => (
                                    <Button
                                        key={type}
                                        variant={filterType === type ? "default" : "outline"}
                                        size="sm"
                                        onClick={() => applyQuickFilter(type)}
                                        className="flex-1 sm:flex-none"
                                    >
                                        {label}
                                    </Button>
                                ))}
                            </div>
                        </div>
                    </CardContent>

                    <CardContent className="space-y-4">
                        {/* Filters */}
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
                            <div className="relative flex-1">
                                <Search className="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                                <Input
                                    placeholder="Search by shop name, contact person, email..."
                                    value={searchTerm}
                                    onChange={(e) => handleSearch(e.target.value)}
                                    className="pl-9"
                                />
                            </div>
                            <Select value={selectedStatus || 'all'} onValueChange={handleStatusFilter}>
                                <SelectTrigger className="w-full sm:w-40">
                                    <SelectValue placeholder="Filter by status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Statuses</SelectItem>
                                    {formOptions?.lead_statuses.map((status) => (
                                        <SelectItem key={status.id} value={status.id.toString()}>
                                            {status.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select value={selectedBusinessType || 'all'} onValueChange={handleBusinessTypeFilter}>
                                <SelectTrigger className="w-full sm:w-40">
                                    <SelectValue placeholder="Filter by business" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Business Types</SelectItem>
                                    {formOptions?.business_types.map((type) => (
                                        <SelectItem key={type.id} value={type.id.toString()}>
                                            {type.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select value={selectedAssignedTo || 'all'} onValueChange={handleAssignedToFilter}>
                                <SelectTrigger className="w-full sm:w-40">
                                    <SelectValue placeholder="Assigned to" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Users</SelectItem>
                                    {formOptions?.users.map((user) => (
                                        <SelectItem key={user.id} value={user.id.toString()}>
                                            {user.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        {/* Loading State */}
                        {loading && (
                            <div className="flex items-center justify-center py-8">
                                <div className="text-muted-foreground">Loading leads...</div>
                            </div>
                        )}

                        {/* Leads Table */}
                        {!loading && leads && (
                            <>
                                <div className="rounded-md border">
                                    <div className="overflow-x-auto">
                                        <table className="w-full">
                                            <thead className="border-b bg-muted/50">
                                                <tr>
                                                    <th className="p-3 text-left font-medium">Create On</th>
                                                    <th className="p-3 text-left font-medium">Shop Details</th>
                                                    <th className="p-3 text-left font-medium">Contact</th>
                                                    <th className="p-3 text-left font-medium">Business Info</th>
                                                    <th className="p-3 text-left font-medium">Status & Rating</th>
                                                    <th className="p-3 text-left font-medium">Assigned To</th>
                                                    <th className="p-3 text-left font-medium">Next Follow-up</th>
                                                    <th className="p-3 text-right font-medium">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {leads.data.map((lead) => (
                                                    <tr key={lead.id} className="border-b hover:bg-muted/25">
                                                        <td className="p-3">
                                                            <div className="text-sm">
                                                                {formatDate(lead.created_at)}
                                                            </div>
                                                        </td>
                                                        <td className="p-3">
                                                            <div>
                                                                <div className="font-medium">
                                                                    <a href={`/leads/${lead.id}`} className="flex items-center gap-2 hover:underline">
                                                                        <ExternalLink size={15} /> {lead.shop_name}
                                                                    </a>
                                                                </div>
                                                                <div className="text-sm text-muted-foreground">
                                                                    {lead.area_locality && (
                                                                        <div className="flex items-center gap-1">
                                                                            <MapPin className="h-3 w-3" />
                                                                            {lead.area_locality}
                                                                        </div>
                                                                    )}
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td className="p-3">
                                                            <div>
                                                                <div className="font-medium">{lead.contact_person}</div>
                                                                <div className="text-sm text-muted-foreground space-y-1">
                                                                    <div className="flex items-center gap-1">
                                                                        <Phone className="h-3 w-3" />
                                                                        {lead.mobile_number}
                                                                    </div>
                                                                    {lead.email && (
                                                                        <div className="flex items-center gap-1">
                                                                            <Mail className="h-3 w-3" />
                                                                            {lead.email}
                                                                        </div>
                                                                    )}
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td className="p-3">
                                                            <div className="space-y-1">
                                                                {lead.business_type_data && (
                                                                    <Badge variant="outline" className="text-xs bg-blue-900">
                                                                        {lead.business_type_data.name}
                                                                    </Badge>
                                                                )}
                                                            </div>
                                                        </td>
                                                        <td className="p-3">
                                                            <div className="space-y-2">
                                                                {lead.lead_status_data && (
                                                                    lead.lead_status_data.name == 'Sold' ?
                                                                        <Badge className='bg-yellow-600' variant={getStatusBadgeVariant(lead.lead_status_data.name)}>
                                                                            {lead.lead_status_data.name}
                                                                        </Badge>
                                                                        :
                                                                        <Badge variant={getStatusBadgeVariant(lead.lead_status_data.name)}>
                                                                            {lead.lead_status_data.name}
                                                                        </Badge>
                                                                )}
                                                                
                                                            </div>
                                                        </td>
                                                        <td className="p-3">
                                                            <div className="text-sm">
                                                                {lead.assigned_to_user ? (
                                                                    <Badge className='bg-blue-500' variant="secondary">
                                                                        {lead.assigned_to_user.name}
                                                                    </Badge>
                                                                ) : (
                                                                    <span className="text-muted-foreground">Unassigned</span>
                                                                )}
                                                            </div>
                                                        </td>
                                                        <td className="p-3">
                                                            <div
                                                                className={`text-sm ${lead.next_follow_up_date &&
                                                                    (new Date(lead.next_follow_up_date).getTime() < new Date().setHours(0, 0, 0, 0))
                                                                    ? "text-red-500"
                                                                    : "text-green-500"
                                                                    }`}
                                                            >
                                                                {formatDate(lead.next_follow_up_date)}
                                                            </div>
                                                        </td>

                                                        <td className="p-3">
                                                            <div className="flex items-center justify-end gap-2">
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => handleEditLead(lead)}
                                                                    className="h-8 w-8 p-0"
                                                                >
                                                                    <Edit2 className="h-4 w-4" />
                                                                </Button>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => handleDeleteLead(lead.id)}
                                                                    className="h-8 w-8 p-0 text-destructive hover:text-destructive"
                                                                >
                                                                    <Trash2 className="h-4 w-4" />
                                                                </Button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                {/* Pagination */}
                                {leads.last_page > 1 && (
                                    <div className="flex items-center justify-between">
                                        <div className="text-sm text-muted-foreground">
                                            Showing {leads.from} to {leads.to} of {leads.total} results
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => setCurrentPage(currentPage - 1)}
                                                disabled={leads.current_page === 1}
                                            >
                                                Previous
                                            </Button>
                                            {generatePaginationItems().map((page) => (
                                                <Button
                                                    key={page}
                                                    variant={page === leads.current_page ? "default" : "outline"}
                                                    size="sm"
                                                    onClick={() => setCurrentPage(page)}
                                                    className="h-8 w-8 p-0"
                                                >
                                                    {page}
                                                </Button>
                                            ))}
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => setCurrentPage(currentPage + 1)}
                                                disabled={leads.current_page === leads.last_page}
                                            >
                                                Next
                                            </Button>
                                        </div>
                                    </div>
                                )}

                                {/* Results count and per page selector */}
                                <div className="flex items-center justify-between border-t pt-4">
                                    <div className="flex items-center gap-2">
                                        <span className="text-sm text-muted-foreground">Per page:</span>
                                        <Select value={perPage.toString()} onValueChange={(value) => {
                                            setPerPage(parseInt(value));
                                            setCurrentPage(1);
                                        }}>
                                            <SelectTrigger className="w-20">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="5">5</SelectItem>
                                                <SelectItem value="10">10</SelectItem>
                                                <SelectItem value="25">25</SelectItem>
                                                <SelectItem value="50">50</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>
                            </>
                        )}

                        {/* Empty state */}
                        {!loading && leads && leads.data.length === 0 && (
                            <div className="flex flex-col items-center justify-center py-8">
                                <div className="text-muted-foreground">No leads found</div>
                                <div className="text-sm text-muted-foreground">
                                    Try adjusting your search or filter criteria
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            {/* Lead Modal */}
            {formOptions && (
                <LeadModal
                    isOpen={isModalOpen}
                    onClose={handleModalClose}
                    onSave={handleLeadSaved}
                    lead={editingLead}
                    formOptions={formOptions}
                />
            )}
        </AppLayout>
    );
}
