import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Separator } from '@/components/ui/separator';
import Heading from '@/components/heading';
import AppLayout from '@/layouts/app-layout';
import { 
    Calendar, 
    Clock, 
    MapPin, 
    Camera, 
    Activity, 
    Pause, 
    CheckCircle, 
    AlertCircle,
    Navigation,
    Timer,
    Coffee,
    User,
    ArrowLeft
} from 'lucide-react';
import { MapContainer, TileLayer, Marker, Popup, Polyline } from 'react-leaflet';
import { LatLngExpression } from 'leaflet';
import L from 'leaflet';
import axios from 'axios';
import dayjs from 'dayjs';
import { router } from '@inertiajs/react';

// Fix for default markers in react-leaflet
delete (L.Icon.Default.prototype as any)._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png',
    iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
});

interface Meeting {
    id: number;
    lead_id: number;
    meeting_start_time: string;
    meeting_end_time?: string;
    meeting_start_latitude?: number;
    meeting_start_longitude?: number;
    meeting_end_latitude?: number;
    meeting_end_longitude?: number;
    meeting_end_notes?: string;
    created_at: string;
    updated_at: string;
    lead_details?: {
        id: number;
        shop_name: string;
        contact_person: string;
        mobile_number: string;
        address?: string;
        area_locality?: string;
        pincode?: string;
        gps_location?: string;
        business_type_data?: {
            id: number;
            name: string;
        };
        current_system_data?: {
            id: number;
            name: string;
        };
        lead_status_data?: {
            id: number;
            name: string;
        };
    };
    time_from_previous_meeting?: {
        hours: number;
        minutes: number;
        total_minutes: number;
        formatted: string;
    };
    distance_from_previous_meeting?: {
        kilometers: number;
        meters: number;
    };
}

interface UserShift {
    id: number;
    user_id: number;
    shift_date: string;
    shift_start: string | null;
    shift_end: string | null;
    shift_start_latitude: number | null;
    shift_start_longitude: number | null;
    shift_end_latitude: number | null;
    shift_end_longitude: number | null;
    shift_start_selfie_url: string | null;
    shift_end_selfie_url: string | null;
    break_start: string | null;
    break_end: string | null;
    total_break_mins: number | null;
    meetings?: Meeting[];
    meetings_count?: number;
    total_meeting_time_minutes?: number;
    user: {
        id: number;
        name: string;
        email: string;
        designation?: string;
    };
}

interface PaginatedShifts {
    data: UserShift[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface UserShiftsPageProps {
    user: {
        id: number;
        name: string;
        email: string;
        designation?: string;
    };
    shifts?: any;
    filters?: any;
}

export default function UserShiftsPage({ user, shifts: initialShifts, filters }: UserShiftsPageProps) {
    const [shifts, setShifts] = useState<PaginatedShifts | null>(initialShifts || null);
    const [loading, setLoading] = useState(false);
    const [startDate, setStartDate] = useState(filters?.start_date || '');
    const [endDate, setEndDate] = useState(filters?.end_date || '');
    const [selectedShift, setSelectedShift] = useState<UserShift | null>(null);
    const [selectedImage, setSelectedImage] = useState<string | null>(null);

    const fetchShifts = async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams({
                per_page: '50'
            });

            if (startDate) params.append('start_date', startDate);
            if (endDate) params.append('end_date', endDate);

            const response = await axios.get(`/api/users/${user.id}/shifts?${params.toString()}`);
            setShifts(response.data.shifts);
            
            // Auto-select the first shift if available
            if (response.data.shifts.data.length > 0 && !selectedShift) {
                setSelectedShift(response.data.shifts.data[0]);
            }
        } catch (error) {
            console.error('Error fetching user shifts:', error);
        }
        setLoading(false);
    };

    useEffect(() => {
        fetchShifts();
    }, [user.id, startDate, endDate]);

    // Set default date range to last 30 days if no filters provided
    useEffect(() => {
        if (!filters?.start_date && !filters?.end_date) {
            const today = dayjs();
            const thirtyDaysAgo = today.subtract(30, 'day');
            setStartDate(thirtyDaysAgo.format('YYYY-MM-DD'));
            setEndDate(today.format('YYYY-MM-DD'));
        }
    }, [filters]);

    // Auto-select first shift if initial data is provided
    useEffect(() => {
        if (initialShifts?.data?.length > 0 && !selectedShift) {
            setSelectedShift(initialShifts.data[0]);
        }
    }, [initialShifts, selectedShift]);

    const formatDateTime = (dateTime: string | null) => {
        if (!dateTime) return '-';
        return dayjs(dateTime).format('MMM DD, YYYY HH:mm');
    };

    const formatTime = (dateTime: string | null) => {
        if (!dateTime) return '-';
        return dayjs(dateTime).format('HH:mm');
    };

    const formatDuration = (start: string | null, end: string | null) => {
        if (!start || !end) return '-';
        const duration = dayjs(end).diff(dayjs(start), 'minute');
        const hours = Math.floor(duration / 60);
        const minutes = duration % 60;
        return `${hours}h ${minutes}m`;
    };

    const formatCoordinate = (coordinate: number | string | null | undefined, decimals: number = 6): string => {
        if (coordinate === null || coordinate === undefined || coordinate === '') {
            return 'N/A';
        }
        const num = Number(coordinate);
        if (isNaN(num)) {
            return 'Invalid';
        }
        return num.toFixed(decimals);
    };

    const formatMeetingCoordinate = (coordinate: number | string | null | undefined, decimals: number = 4): string => {
        if (coordinate === null || coordinate === undefined || coordinate === '') {
            return 'N/A';
        }
        const num = Number(coordinate);
        if (isNaN(num)) {
            return 'Invalid';
        }
        return num.toFixed(decimals);
    };

    const getUserInitials = (name: string | undefined | null) => {
        if (!name || typeof name !== 'string') {
            return 'U';
        }
        return name
            .split(' ')
            .map(word => word.charAt(0))
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    const getShiftStatusBadge = (shift: UserShift) => {
        if (!shift.shift_start) {
            return <Badge variant="outline" className="text-xs">Not Started</Badge>;
        }
        
        if (shift.shift_start && !shift.shift_end) {
            if (shift.break_start && !shift.break_end) {
                return <Badge variant="secondary" className="text-xs">On Break</Badge>;
            }
            return <Badge variant="default" className="text-xs">In Progress</Badge>;
        }
        
        if (shift.shift_start && shift.shift_end) {
            return <Badge variant="outline" className="text-xs text-green-600">Completed</Badge>;
        }
        
        return <Badge variant="destructive" className="text-xs">Unknown</Badge>;
    };

    const openImageModal = (imageUrl: string) => {
        setSelectedImage(imageUrl);
    };

    const getMapCenter = (): LatLngExpression => {
        if (selectedShift?.shift_start_latitude && selectedShift?.shift_start_longitude) {
            return [selectedShift.shift_start_latitude, selectedShift.shift_start_longitude];
        }
        // Default to a central location if no coordinates
        return [28.6139, 77.2090]; // New Delhi coordinates as fallback
    };

    const getMapMarkers = () => {
        if (!selectedShift) return [];
        
        const markers = [];
        
        // Start location marker
        if (selectedShift.shift_start_latitude && selectedShift.shift_start_longitude) {
            markers.push({
                position: [selectedShift.shift_start_latitude, selectedShift.shift_start_longitude] as LatLngExpression,
                type: 'start',
                time: selectedShift.shift_start,
                selfie: selectedShift.shift_start_selfie_url
            });
        }
        
        // End location marker
        if (selectedShift.shift_end_latitude && selectedShift.shift_end_longitude) {
            markers.push({
                position: [selectedShift.shift_end_latitude, selectedShift.shift_end_longitude] as LatLngExpression,
                type: 'end',
                time: selectedShift.shift_end,
                selfie: selectedShift.shift_end_selfie_url
            });
        }
        
        return markers;
    };

    const getPolylineCoordinates = (): LatLngExpression[] => {
        if (!selectedShift) return [];
        
        const coordinates: LatLngExpression[] = [];
        
        if (selectedShift.shift_start_latitude && selectedShift.shift_start_longitude) {
            coordinates.push([selectedShift.shift_start_latitude, selectedShift.shift_start_longitude]);
        }
        
        if (selectedShift.shift_end_latitude && selectedShift.shift_end_longitude) {
            coordinates.push([selectedShift.shift_end_latitude, selectedShift.shift_end_longitude]);
        }
        
        return coordinates;
    };

    const createCustomIcon = (type: 'start' | 'end') => {
        const color = type === 'start' ? '#22c55e' : '#ef4444';
        const iconHtml = `
            <div style="
                background-color: ${color};
                width: 30px;
                height: 30px;
                border-radius: 50%;
                border: 3px solid white;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            ">
                <span style="color: white; font-weight: bold; font-size: 12px;">
                    ${type === 'start' ? 'S' : 'E'}
                </span>
            </div>
        `;
        
        return L.divIcon({
            html: iconHtml,
            className: 'custom-marker',
            iconSize: [30, 30],
            iconAnchor: [15, 15],
        });
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Users', href: '/users' },
                { title: `${user.name} - Shifts`, href: `/users/${user.id}/shifts` }
            ]}
        >
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button 
                            variant="outline" 
                            size="sm"
                            onClick={() => router.visit('/users')}
                            className="flex items-center gap-2"
                        >
                            <ArrowLeft className="h-4 w-4" />
                            Back to Users
                        </Button>
                        <div className="flex items-center gap-3">
                            <Avatar className="h-10 w-10">
                                <AvatarFallback>{getUserInitials(user.name)}</AvatarFallback>
                            </Avatar>
                            <div>
                                <Heading title={`${user.name} - Shift Tracking`} />
                                <p className="text-sm text-muted-foreground">
                                    {user.designation && `${user.designation} • `}{user.email}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    {/* Left Panel - Shift List */}
                    <div className="lg:col-span-1 space-y-4">
                        {/* Date Filters */}
                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-base">Date Range</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <div>
                                    <label className="text-sm font-medium">Start Date</label>
                                    <Input
                                        type="date"
                                        value={startDate}
                                        onChange={(e) => setStartDate(e.target.value)}
                                    />
                                </div>
                                <div>
                                    <label className="text-sm font-medium">End Date</label>
                                    <Input
                                        type="date"
                                        value={endDate}
                                        onChange={(e) => setEndDate(e.target.value)}
                                    />
                                </div>
                                <Button onClick={fetchShifts} className="w-full" size="sm">
                                    Apply Filter
                                </Button>
                            </CardContent>
                        </Card>

                        {/* Shift List */}
                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-base">Shift History</CardTitle>
                                <CardDescription>
                                    {shifts?.total || 0} shifts found
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-2 max-h-[500px] overflow-y-auto">
                                {loading && (
                                    <div className="text-center py-4 text-muted-foreground">
                                        Loading shifts...
                                    </div>
                                )}
                                
                                {!loading && shifts && shifts.data.length === 0 && (
                                    <div className="text-center py-4 text-muted-foreground">
                                        No shifts found for the selected period
                                    </div>
                                )}

                                {!loading && shifts && shifts.data.map((shift) => (
                                    <div
                                        key={shift.id}
                                        className={`p-3 rounded-lg border cursor-pointer transition-colors ${
                                            selectedShift?.id === shift.id 
                                                ? 'border-primary bg-primary/5' 
                                                : 'border-border hover:bg-muted/50'
                                        }`}
                                        onClick={() => setSelectedShift(shift)}
                                    >
                                        <div className="flex items-center justify-between mb-2">
                                            <div className="flex items-center gap-2">
                                                <Calendar className="h-4 w-4 text-muted-foreground" />
                                                <span className="font-medium text-sm">
                                                    {dayjs(shift.shift_date).format('MMM DD, YYYY')}
                                                </span>
                                            </div>
                                            {getShiftStatusBadge(shift)}
                                        </div>
                                        
                                        <div className="space-y-1 text-xs text-muted-foreground">
                                            <div className="flex items-center gap-1">
                                                <Clock className="h-3 w-3" />
                                                {formatTime(shift.shift_start)} - {formatTime(shift.shift_end)}
                                            </div>
                                            
                                            {shift.shift_start_latitude && shift.shift_start_longitude && (
                                                <div className="flex items-center gap-1">
                                                    <MapPin className="h-3 w-3" />
                                                    Location Available
                                                </div>
                                            )}
                                            
                                            {shift.total_break_mins && (
                                                <div className="flex items-center gap-1">
                                                    <Coffee className="h-3 w-3" />
                                                    {Math.floor(shift.total_break_mins / 60)}h {shift.total_break_mins % 60}m break
                                                </div>
                                            )}
                                            
                                            {shift.meetings_count && shift.meetings_count > 0 && (
                                                <div className="flex items-center gap-1">
                                                    <User className="h-3 w-3" />
                                                    {shift.meetings_count} meetings
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    </div>

                    {/* Right Panel - Map and Details */}
                    <div className="lg:col-span-2 space-y-4">
                        {selectedShift ? (
                            <>
                                {/* Shift Details */}
                                <Card>
                                    <CardHeader className="pb-3">
                                        <CardTitle className="flex items-center gap-2">
                                            <Activity className="h-4 w-4" />
                                            Shift Details - {dayjs(selectedShift.shift_date).format('MMMM DD, YYYY')}
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                                            {/* Start Time */}
                                            <div className="space-y-1">
                                                <div className="text-sm font-medium text-green-600">Start Time</div>
                                                <div className="text-sm">{formatDateTime(selectedShift.shift_start)}</div>
                                            </div>
                                            
                                            {/* End Time */}
                                            <div className="space-y-1">
                                                <div className="text-sm font-medium text-red-600">End Time</div>
                                                <div className="text-sm">{formatDateTime(selectedShift.shift_end)}</div>
                                            </div>
                                            
                                            {/* Duration */}
                                            <div className="space-y-1">
                                                <div className="text-sm font-medium text-blue-600">Duration</div>
                                                <div className="text-sm">{formatDuration(selectedShift.shift_start, selectedShift.shift_end)}</div>
                                            </div>
                                            
                                            {/* Break Time */}
                                            <div className="space-y-1">
                                                <div className="text-sm font-medium text-orange-600">Break Time</div>
                                                <div className="text-sm">
                                                    {selectedShift.total_break_mins ? 
                                                        `${Math.floor(selectedShift.total_break_mins / 60)}h ${selectedShift.total_break_mins % 60}m` : 
                                                        'No breaks'
                                                    }
                                                </div>
                                            </div>
                                        </div>

                                        <Separator className="my-4" />

                                        {/* Location and Selfies */}
                                        <div className="grid gap-4 md:grid-cols-2">
                                            {/* Start Location & Selfie */}
                                            <div className="space-y-3">
                                                <h4 className="font-medium text-green-600 flex items-center gap-2">
                                                    <Navigation className="h-4 w-4" />
                                                    Shift Start
                                                </h4>
                                                <div className="space-y-2">
                                                    {selectedShift.shift_start_latitude && selectedShift.shift_start_longitude ? (
                                                        <div className="text-sm">
                                                            <strong>Location:</strong> {formatCoordinate(selectedShift.shift_start_latitude, 4)}, {formatCoordinate(selectedShift.shift_start_longitude, 4)}
                                                        </div>
                                                    ) : (
                                                        <div className="text-sm text-muted-foreground">No location data</div>
                                                    )}
                                                    
                                                    {selectedShift.shift_start_selfie_url && (
                                                        <div>
                                                            <div className="text-sm font-medium mb-1">Selfie:</div>
                                                            <img
                                                                src={selectedShift.shift_start_selfie_url}
                                                                alt="Start selfie"
                                                                className="w-20 h-20 object-cover rounded cursor-pointer hover:opacity-80"
                                                                onClick={() => openImageModal(selectedShift.shift_start_selfie_url!)}
                                                            />
                                                        </div>
                                                    )}
                                                </div>
                                            </div>

                                            {/* End Location & Selfie */}
                                            <div className="space-y-3">
                                                <h4 className="font-medium text-red-600 flex items-center gap-2">
                                                    <Navigation className="h-4 w-4" />
                                                    Shift End
                                                </h4>
                                                <div className="space-y-2">
                                                    {selectedShift.shift_end_latitude && selectedShift.shift_end_longitude ? (
                                                        <div className="text-sm">
                                                            <strong>Location:</strong> {formatCoordinate(selectedShift.shift_end_latitude, 4)}, {formatCoordinate(selectedShift.shift_end_longitude, 4)}
                                                        </div>
                                                    ) : (
                                                        <div className="text-sm text-muted-foreground">No location data</div>
                                                    )}
                                                    
                                                    {selectedShift.shift_end_selfie_url && (
                                                        <div>
                                                            <div className="text-sm font-medium mb-1">Selfie:</div>
                                                            <img
                                                                src={selectedShift.shift_end_selfie_url}
                                                                alt="End selfie"
                                                                className="w-20 h-20 object-cover rounded cursor-pointer hover:opacity-80"
                                                                onClick={() => openImageModal(selectedShift.shift_end_selfie_url!)}
                                                            />
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>

                                {/* Meetings Section */}
                                {selectedShift.meetings && selectedShift.meetings.length > 0 && (
                                    <Card>
                                        <CardHeader className="pb-3">
                                            <CardTitle className="flex items-center gap-2">
                                                <User className="h-4 w-4" />
                                                Meetings - {selectedShift.meetings_count} meetings ({Math.floor((selectedShift.total_meeting_time_minutes || 0) / 60)}h {(selectedShift.total_meeting_time_minutes || 0) % 60}m total)
                                            </CardTitle>
                                            <CardDescription>
                                                Meeting details with time between meetings and distances
                                            </CardDescription>
                                        </CardHeader>
                                        <CardContent>
                                            <div className="space-y-4">
                                                {selectedShift.meetings.map((meeting, index) => (
                                                    <div key={meeting.id} className="border rounded-lg p-4">
                                                        <div className="flex items-center justify-between mb-3">
                                                            <div className="flex items-center gap-2">
                                                                <div className="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-medium text-sm">
                                                                    {index + 1}
                                                                </div>
                                                                <div>
                                                                    <h4 className="font-medium">{meeting.lead_details?.shop_name || 'Unknown Shop'}</h4>
                                                                    <p className="text-sm text-muted-foreground">
                                                                        {meeting.lead_details?.contact_person || 'N/A'} • {meeting.lead_details?.mobile_number || 'N/A'}
                                                                    </p>
                                                                </div>
                                                            </div>
                                                            <div className="text-right">
                                                                <div className="text-sm font-medium">
                                                                    {dayjs(meeting.meeting_start_time).format('HH:mm')} - {meeting.meeting_end_time ? dayjs(meeting.meeting_end_time).format('HH:mm') : 'Ongoing'}
                                                                </div>
                                                                {meeting.meeting_end_time && (
                                                                    <div className="text-xs text-muted-foreground">
                                                                        {Math.floor(dayjs(meeting.meeting_end_time).diff(dayjs(meeting.meeting_start_time), 'minute') / 60)}h {dayjs(meeting.meeting_end_time).diff(dayjs(meeting.meeting_start_time), 'minute') % 60}m
                                                                    </div>
                                                                )}
                                                            </div>
                                                        </div>

                                                        <div className="grid gap-3 md:grid-cols-2">
                                                            {/* Lead Details */}
                                                            <div className="space-y-2">
                                                                <h5 className="font-medium text-sm">Lead Information</h5>
                                                                <div className="text-sm space-y-1">
                                                                    <div><strong>Address:</strong> {meeting.lead_details?.address || 'N/A'}</div>
                                                                    <div><strong>Area:</strong> {meeting.lead_details?.area_locality || 'N/A'}</div>
                                                                    <div><strong>Pincode:</strong> {meeting.lead_details?.pincode || 'N/A'}</div>
                                                                    {meeting.lead_details?.business_type_data && (
                                                                        <div><strong>Business Type:</strong> {meeting.lead_details.business_type_data.name}</div>
                                                                    )}
                                                                    {meeting.lead_details?.lead_status_data && (
                                                                        <div><strong>Status:</strong> {meeting.lead_details.lead_status_data.name}</div>
                                                                    )}
                                                                </div>
                                                            </div>

                                                            {/* Time & Distance Info */}
                                                            <div className="space-y-2">
                                                                <h5 className="font-medium text-sm">Travel Information</h5>
                                                                <div className="text-sm space-y-1">
                                                                    {meeting.time_from_previous_meeting ? (
                                                                        <div>
                                                                            <strong>Time from previous meeting:</strong> {meeting.time_from_previous_meeting.formatted}
                                                                        </div>
                                                                    ) : (
                                                                        <div className="text-muted-foreground">First meeting of the day</div>
                                                                    )}
                                                                    
                                                                    {meeting.distance_from_previous_meeting ? (
                                                                        <div>
                                                                            <strong>Distance from previous meeting:</strong> {meeting.distance_from_previous_meeting.kilometers} km ({meeting.distance_from_previous_meeting.meters} m)
                                                                        </div>
                                                                    ) : (
                                                                        <div className="text-muted-foreground">No distance data available</div>
                                                                    )}

                                                                    {meeting.meeting_start_latitude && meeting.meeting_start_longitude && (
                                                                        <div>
                                                                            <strong>Meeting Location:</strong> {formatMeetingCoordinate(meeting.meeting_start_latitude)}, {formatMeetingCoordinate(meeting.meeting_start_longitude)}
                                                                        </div>
                                                                    )}
                                                                </div>
                                                            </div>
                                                        </div>

                                                        {meeting.meeting_end_notes && (
                                                            <div className="mt-3 pt-3 border-t">
                                                                <h5 className="font-medium text-sm mb-1">Meeting Notes</h5>
                                                                <p className="text-sm text-muted-foreground">{meeting.meeting_end_notes}</p>
                                                            </div>
                                                        )}
                                                    </div>
                                                ))}
                                            </div>
                                        </CardContent>
                                    </Card>
                                )}

                                {/* Map */}
                                <Card>
                                    <CardHeader className="pb-3">
                                        <CardTitle className="flex items-center gap-2">
                                            <MapPin className="h-4 w-4" />
                                            Location Tracking
                                        </CardTitle>
                                        <CardDescription>
                                            Visual representation of shift locations and movement
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="h-[400px] rounded-lg overflow-hidden">
                                            <MapContainer
                                                center={getMapCenter()}
                                                zoom={13}
                                                style={{ height: '100%', width: '100%' }}
                                            >
                                                <TileLayer
                                                    url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                                                    attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                                                />
                                                
                                                {/* Markers */}
                                                {getMapMarkers().map((marker, index) => (
                                                    <Marker
                                                        key={index}
                                                        position={marker.position}
                                                        icon={createCustomIcon(marker.type)}
                                                    >
                                                        <Popup>
                                                            <div className="space-y-2">
                                                                <div className="font-medium">
                                                                    {marker.type === 'start' ? 'Shift Start' : 'Shift End'}
                                                                </div>
                                                                <div className="text-sm">
                                                                    <strong>Time:</strong> {formatDateTime(marker.time)}
                                                                </div>
                                                                <div className="text-sm">
                                                                    <strong>Coordinates:</strong><br />
                                                                    {marker.position[0]}, {marker.position[1]}
                                                                </div>
                                                                {marker.selfie && (
                                                                    <div className="mt-2">
                                                                        <img
                                                                            src={marker.selfie}
                                                                            alt={`${marker.type} selfie`}
                                                                            className="w-24 h-24 object-cover rounded cursor-pointer"
                                                                            onClick={() => openImageModal(marker.selfie!)}
                                                                        />
                                                                    </div>
                                                                )}
                                                            </div>
                                                        </Popup>
                                                    </Marker>
                                                ))}
                                                
                                                {/* Polyline connecting start and end */}
                                                {getPolylineCoordinates().length > 1 && (
                                                    <Polyline
                                                        positions={getPolylineCoordinates()}
                                                        color="#3b82f6"
                                                        weight={3}
                                                        opacity={0.7}
                                                        dashArray="5, 10"
                                                    />
                                                )}
                                            </MapContainer>
                                        </div>
                                        
                                        {/* Map Legend */}
                                        <div className="mt-3 flex items-center gap-4 text-sm">
                                            <div className="flex items-center gap-2">
                                                <div className="w-4 h-4 rounded-full bg-green-500"></div>
                                                <span>Shift Start</span>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <div className="w-4 h-4 rounded-full bg-red-500"></div>
                                                <span>Shift End</span>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <div className="w-4 h-1 bg-blue-500" style={{borderStyle: 'dashed'}}></div>
                                                <span>Movement Path</span>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            </>
                        ) : (
                            <Card>
                                <CardContent className="py-12">
                                    <div className="text-center text-muted-foreground">
                                        <MapPin className="h-12 w-12 mx-auto mb-4 opacity-50" />
                                        <h3 className="text-lg font-medium mb-2">Select a Shift</h3>
                                        <p>Choose a shift from the list to view detailed information and location tracking</p>
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </div>

            {/* Image Modal */}
            {selectedImage && (
                <div 
                    className="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50"
                    onClick={() => setSelectedImage(null)}
                >
                    <div className="relative max-w-4xl max-h-[90vh] p-4">
                        <img
                            src={selectedImage}
                            alt="Shift selfie"
                            className="max-w-full max-h-full object-contain rounded"
                        />
                        <Button
                            variant="secondary"
                            size="sm"
                            className="absolute top-2 right-2"
                            onClick={() => setSelectedImage(null)}
                        >
                            ✕
                        </Button>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}

