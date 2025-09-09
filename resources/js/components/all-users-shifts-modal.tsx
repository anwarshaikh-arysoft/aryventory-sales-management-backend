import { useState, useEffect } from 'react';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Calendar, Clock, MapPin, Camera, X, Users, Activity, Pause, CheckCircle, AlertCircle } from 'lucide-react';
import axios from 'axios';

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
    user: {
        id: number;
        name: string;
        email: string;
        designation?: string;
    };
}

interface CurrentShiftStatus {
    user: {
        id: number;
        name: string;
        email: string;
        designation?: string;
    };
    shift: UserShift;
    status: 'active' | 'break' | 'completed' | 'offline';
    status_text: string;
    current_activity: string | null;
    work_duration: number;
    break_duration: number;
}

interface PaginatedShifts {
    data: UserShift[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface AllUsersShiftsModalProps {
    isOpen: boolean;
    onClose: () => void;
}

export default function AllUsersShiftsModal({ isOpen, onClose }: AllUsersShiftsModalProps) {
    const [shifts, setShifts] = useState<PaginatedShifts | null>(null);
    const [currentStatuses, setCurrentStatuses] = useState<CurrentShiftStatus[]>([]);
    const [loading, setLoading] = useState(false);
    const [statusLoading, setStatusLoading] = useState(false);
    const [startDate, setStartDate] = useState('');
    const [endDate, setEndDate] = useState('');
    const [selectedUserId, setSelectedUserId] = useState('');
    const [currentPage, setCurrentPage] = useState(1);
    const [selectedImage, setSelectedImage] = useState<string | null>(null);
    const [activeTab, setActiveTab] = useState('current');

    const fetchCurrentStatus = async () => {
        setStatusLoading(true);
        try {
            const response = await axios.get('/api/shift/current-status');
            setCurrentStatuses(response.data.current_shifts || []);
        } catch (error) {
            console.error('Error fetching current status:', error);
            setCurrentStatuses([]);
        }
        setStatusLoading(false);
    };

    const fetchAllShifts = async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams({
                page: currentPage.toString(),
                per_page: '20'
            });

            if (startDate) params.append('start_date', startDate);
            if (endDate) params.append('end_date', endDate);
            if (selectedUserId) params.append('user_id', selectedUserId);

            const response = await axios.get(`/api/shift/all-users-shifts?${params.toString()}`);
            setShifts(response.data.shifts);
        } catch (error) {
            console.error('Error fetching all shifts:', error);
        }
        setLoading(false);
    };

    useEffect(() => {
        if (isOpen) {
            fetchCurrentStatus();
            fetchAllShifts();
        }
    }, [isOpen, currentPage, startDate, endDate, selectedUserId]);

    const formatDateTime = (dateTime: string | null) => {
        if (!dateTime) return '-';
        return new Date(dateTime).toLocaleString();
    };

    const formatDuration = (minutes: number) => {
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        return `${hours}h ${mins}m`;
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

    const getUserInitials = (name: string) => {
        return name
            .split(' ')
            .map(word => word.charAt(0))
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    const getStatusBadge = (status: string) => {
        const variants = {
            active: { variant: 'default' as const, icon: Activity, color: 'text-green-600' },
            break: { variant: 'secondary' as const, icon: Pause, color: 'text-orange-600' },
            completed: { variant: 'outline' as const, icon: CheckCircle, color: 'text-blue-600' },
            offline: { variant: 'destructive' as const, icon: AlertCircle, color: 'text-red-600' }
        };
        
        const config = variants[status as keyof typeof variants] || variants.offline;
        const Icon = config.icon;
        
        return (
            <Badge variant={config.variant} className="flex items-center gap-1">
                <Icon className="h-3 w-3" />
                {status === 'active' ? 'Working' : 
                 status === 'break' ? 'On Break' :
                 status === 'completed' ? 'Completed' : 'Offline'}
            </Badge>
        );
    };

    const openImageModal = (imageUrl: string) => {
        setSelectedImage(imageUrl);
    };

    return (
        <>
            <Dialog open={isOpen} onOpenChange={onClose}>
                <DialogContent className="max-w-7xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <Users className="h-5 w-5" />
                            Users Shift Management
                        </DialogTitle>
                        <DialogDescription>
                            Monitor all users' shift status, history, locations, and selfie verification
                        </DialogDescription>
                    </DialogHeader>

                    <Tabs value={activeTab} onValueChange={setActiveTab} className="w-full">
                        <TabsList className="grid w-full grid-cols-2">
                            <TabsTrigger value="current">Current Status</TabsTrigger>
                            <TabsTrigger value="history">Shift History</TabsTrigger>
                        </TabsList>

                        <TabsContent value="current" className="space-y-4">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <Activity className="h-4 w-4" />
                                        Real-time Shift Status
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {statusLoading && (
                                        <div className="flex justify-center py-8">
                                            <div className="text-muted-foreground">Loading current status...</div>
                                        </div>
                                    )}

                                    {!statusLoading && currentStatuses.length === 0 && (
                                        <div className="text-center py-8 text-muted-foreground">
                                            No active shifts found for today
                                        </div>
                                    )}

                                    {!statusLoading && currentStatuses.length > 0 && (
                                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                            {currentStatuses.map((userStatus) => (
                                                <Card key={userStatus.user.id} className="p-4">
                                                    <div className="space-y-3">
                                                        {/* User Info */}
                                                        <div className="flex items-center gap-3">
                                                            <Avatar className="h-10 w-10">
                                                                <AvatarFallback className="text-sm">
                                                                    {getUserInitials(userStatus.user.name)}
                                                                </AvatarFallback>
                                                            </Avatar>
                                                            <div className="flex-1">
                                                                <div className="font-medium">{userStatus.user.name}</div>
                                                                <div className="text-sm text-muted-foreground">
                                                                    {userStatus.user.designation || 'No designation'}
                                                                </div>
                                                            </div>
                                                            {getStatusBadge(userStatus.status)}
                                                        </div>

                                                        {/* Current Activity */}
                                                        {userStatus.current_activity && (
                                                            <div className="text-sm text-muted-foreground">
                                                                {userStatus.current_activity}
                                                            </div>
                                                        )}

                                                        {/* Duration Info */}
                                                        <div className="grid grid-cols-2 gap-2 text-sm">
                                                            <div>
                                                                <span className="font-medium">Work:</span> {formatDuration(userStatus.work_duration)}
                                                            </div>
                                                            <div>
                                                                <span className="font-medium">Break:</span> {formatDuration(userStatus.break_duration)}
                                                            </div>
                                                        </div>

                                                        {/* Location Info */}
                                                        {userStatus.shift.shift_start_latitude && userStatus.shift.shift_start_longitude && (
                                                            <div className="flex items-center gap-1 text-sm text-muted-foreground">
                                                                <MapPin className="h-3 w-3" />
                                                                Location: {formatCoordinate(userStatus.shift.shift_start_latitude, 4)}, {formatCoordinate(userStatus.shift.shift_start_longitude, 4)}
                                                            </div>
                                                        )}

                                                        {/* Selfies */}
                                                        <div className="flex gap-2">
                                                            {userStatus.shift.shift_start_selfie_url && (
                                                                <div className="text-center">
                                                                    <img
                                                                        src={userStatus.shift.shift_start_selfie_url}
                                                                        alt="Start selfie"
                                                                        className="w-12 h-12 object-cover rounded cursor-pointer hover:opacity-80"
                                                                        onClick={() => openImageModal(userStatus.shift.shift_start_selfie_url!)}
                                                                    />
                                                                    <Badge variant="outline" className="text-xs mt-1">Start</Badge>
                                                                </div>
                                                            )}
                                                            {userStatus.shift.shift_end_selfie_url && (
                                                                <div className="text-center">
                                                                    <img
                                                                        src={userStatus.shift.shift_end_selfie_url}
                                                                        alt="End selfie"
                                                                        className="w-12 h-12 object-cover rounded cursor-pointer hover:opacity-80"
                                                                        onClick={() => openImageModal(userStatus.shift.shift_end_selfie_url!)}
                                                                    />
                                                                    <Badge variant="outline" className="text-xs mt-1">End</Badge>
                                                                </div>
                                                            )}
                                                        </div>
                                                    </div>
                                                </Card>
                                            ))}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <TabsContent value="history" className="space-y-4">
                            {/* Filters */}
                            <Card>
                                <CardContent className="p-4">
                                    <div className="grid gap-4 md:grid-cols-4">
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
                                        <div>
                                            <label className="text-sm font-medium">User ID</label>
                                            <Input
                                                type="number"
                                                placeholder="Filter by user ID"
                                                value={selectedUserId}
                                                onChange={(e) => setSelectedUserId(e.target.value)}
                                            />
                                        </div>
                                        <div className="flex items-end">
                                            <Button onClick={fetchAllShifts} className="w-full">
                                                Apply Filters
                                            </Button>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Shifts History */}
                            {loading && (
                                <div className="flex justify-center py-8">
                                    <div className="text-muted-foreground">Loading shift history...</div>
                                </div>
                            )}

                            {!loading && shifts && (
                                <>
                                    <div className="space-y-4">
                                        {shifts.data.map((shift) => (
                                            <Card key={shift.id}>
                                                <CardContent className="p-6">
                                                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                                        {/* User Info */}
                                                        <div className="space-y-3">
                                                            <div className="flex items-center gap-3">
                                                                <Avatar className="h-8 w-8">
                                                                    <AvatarFallback className="text-xs">
                                                                        {getUserInitials(shift.user.name)}
                                                                    </AvatarFallback>
                                                                </Avatar>
                                                                <div>
                                                                    <div className="font-medium">{shift.user.name}</div>
                                                                    <div className="text-sm text-muted-foreground">
                                                                        {shift.user.designation || 'No designation'}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div className="text-sm">
                                                                <strong>Date:</strong> {new Date(shift.shift_date).toLocaleDateString()}
                                                            </div>
                                                        </div>

                                                        {/* Timing Info */}
                                                        <div className="space-y-2 text-sm">
                                                            <h4 className="font-semibold flex items-center gap-2">
                                                                <Clock className="h-4 w-4" />
                                                                Timings
                                                            </h4>
                                                            <div><strong>Start:</strong> {formatDateTime(shift.shift_start)}</div>
                                                            <div><strong>End:</strong> {formatDateTime(shift.shift_end)}</div>
                                                            <div>
                                                                <strong>Duration:</strong> {
                                                                    shift.shift_start && shift.shift_end
                                                                        ? formatDuration(
                                                                            Math.floor((new Date(shift.shift_end).getTime() - new Date(shift.shift_start).getTime()) / (1000 * 60))
                                                                        )
                                                                        : '-'
                                                                }
                                                            </div>
                                                            {shift.total_break_mins && (
                                                                <div><strong>Break:</strong> {formatDuration(shift.total_break_mins)}</div>
                                                            )}
                                                        </div>

                                                        {/* Location Info */}
                                                        <div className="space-y-2 text-sm">
                                                            <h4 className="font-semibold flex items-center gap-2">
                                                                <MapPin className="h-4 w-4" />
                                                                Locations
                                                            </h4>
                                                            {shift.shift_start_latitude && shift.shift_start_longitude && (
                                                                <div>
                                                                    <strong>Start:</strong> {formatCoordinate(shift.shift_start_latitude)}, {formatCoordinate(shift.shift_start_longitude)}
                                                                </div>
                                                            )}
                                                            {shift.shift_end_latitude && shift.shift_end_longitude && (
                                                                <div>
                                                                    <strong>End:</strong> {formatCoordinate(shift.shift_end_latitude)}, {formatCoordinate(shift.shift_end_longitude)}
                                                                </div>
                                                            )}
                                                            {(!shift.shift_start_latitude || !shift.shift_start_longitude) && 
                                                             (!shift.shift_end_latitude || !shift.shift_end_longitude) && (
                                                                <div className="text-muted-foreground">No location data</div>
                                                            )}
                                                        </div>

                                                        {/* Selfies */}
                                                        <div className="space-y-2">
                                                            <h4 className="font-semibold flex items-center gap-2">
                                                                <Camera className="h-4 w-4" />
                                                                Selfies
                                                            </h4>
                                                            <div className="flex gap-2">
                                                                {shift.shift_start_selfie_url && (
                                                                    <div className="text-center">
                                                                        <img
                                                                            src={shift.shift_start_selfie_url}
                                                                            alt="Start selfie"
                                                                            className="w-16 h-16 object-cover rounded cursor-pointer hover:opacity-80"
                                                                            onClick={() => openImageModal(shift.shift_start_selfie_url!)}
                                                                        />
                                                                        <Badge variant="outline" className="text-xs mt-1">Start</Badge>
                                                                    </div>
                                                                )}
                                                                {shift.shift_end_selfie_url && (
                                                                    <div className="text-center">
                                                                        <img
                                                                            src={shift.shift_end_selfie_url}
                                                                            alt="End selfie"
                                                                            className="w-16 h-16 object-cover rounded cursor-pointer hover:opacity-80"
                                                                            onClick={() => openImageModal(shift.shift_end_selfie_url!)}
                                                                        />
                                                                        <Badge variant="outline" className="text-xs mt-1">End</Badge>
                                                                    </div>
                                                                )}
                                                                {!shift.shift_start_selfie_url && !shift.shift_end_selfie_url && (
                                                                    <div className="text-muted-foreground text-sm">No selfies</div>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </CardContent>
                                            </Card>
                                        ))}
                                    </div>

                                    {/* Pagination */}
                                    {shifts.last_page > 1 && (
                                        <div className="flex justify-center gap-2 mt-6">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => setCurrentPage(currentPage - 1)}
                                                disabled={shifts.current_page === 1}
                                            >
                                                Previous
                                            </Button>
                                            <span className="flex items-center px-3 text-sm">
                                                Page {shifts.current_page} of {shifts.last_page}
                                            </span>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => setCurrentPage(currentPage + 1)}
                                                disabled={shifts.current_page === shifts.last_page}
                                            >
                                                Next
                                            </Button>
                                        </div>
                                    )}

                                    {shifts.data.length === 0 && (
                                        <div className="text-center py-8 text-muted-foreground">
                                            No shift history found for the selected criteria
                                        </div>
                                    )}
                                </>
                            )}
                        </TabsContent>
                    </Tabs>
                </DialogContent>
            </Dialog>

            {/* Image Modal */}
            {selectedImage && (
                <Dialog open={!!selectedImage} onOpenChange={() => setSelectedImage(null)}>
                    <DialogContent className="max-w-3xl">
                        <DialogHeader>
                            <DialogTitle>Shift Selfie</DialogTitle>
                            <Button
                                variant="ghost"
                                size="sm"
                                className="absolute right-4 top-4"
                                onClick={() => setSelectedImage(null)}
                            >
                                <X className="h-4 w-4" />
                            </Button>
                        </DialogHeader>
                        <div className="flex justify-center">
                            <img
                                src={selectedImage}
                                alt="Shift selfie"
                                className="max-w-full max-h-[70vh] object-contain rounded"
                            />
                        </div>
                    </DialogContent>
                </Dialog>
            )}
        </>
    );
}
