import { useState, useEffect } from 'react';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Calendar, Clock, MapPin, Camera, X } from 'lucide-react';
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
    };
}

interface PaginatedShifts {
    data: UserShift[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface UserShiftsModalProps {
    isOpen: boolean;
    onClose: () => void;
    userId: number | null;
}

export default function UserShiftsModal({ isOpen, onClose, userId }: UserShiftsModalProps) {
    const [shifts, setShifts] = useState<PaginatedShifts | null>(null);
    const [loading, setLoading] = useState(false);
    const [startDate, setStartDate] = useState('');
    const [endDate, setEndDate] = useState('');
    const [currentPage, setCurrentPage] = useState(1);
    const [selectedImage, setSelectedImage] = useState<string | null>(null);

    const fetchShifts = async () => {
        if (!userId) return;

        setLoading(true);
        try {
            const params = new URLSearchParams({
                user_id: userId.toString(),
                page: currentPage.toString(),
                per_page: '10'
            });

            if (startDate) params.append('start_date', startDate);
            if (endDate) params.append('end_date', endDate);

            const response = await axios.get(`/api/shift/user-shifts?${params.toString()}`);
            setShifts(response.data.shifts);
        } catch (error) {
            console.error('Error fetching user shifts:', error);
        }
        setLoading(false);
    };

    useEffect(() => {
        if (isOpen && userId) {
            fetchShifts();
        }
    }, [isOpen, userId, currentPage, startDate, endDate]);

    const formatDateTime = (dateTime: string | null) => {
        if (!dateTime) return '-';
        return new Date(dateTime).toLocaleString();
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

    const formatDuration = (start: string | null, end: string | null) => {
        if (!start || !end) return '-';
        const startTime = new Date(start);
        const endTime = new Date(end);
        const diffMs = endTime.getTime() - startTime.getTime();
        const hours = Math.floor(diffMs / (1000 * 60 * 60));
        const minutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
        return `${hours}h ${minutes}m`;
    };

    const openImageModal = (imageUrl: string) => {
        setSelectedImage(imageUrl);
    };

    return (
        <>
            <Dialog open={isOpen} onOpenChange={onClose}>
                <DialogContent className="max-w-6xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>User Shift Details</DialogTitle>
                        <DialogDescription>
                            View shift timings, locations, and selfies for the selected user
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4">
                        {/* Date Filters */}
                        <div className="flex gap-4 items-end">
                            <div className="flex-1">
                                <label className="text-sm font-medium">Start Date</label>
                                <Input
                                    type="date"
                                    value={startDate}
                                    onChange={(e) => setStartDate(e.target.value)}
                                />
                            </div>
                            <div className="flex-1">
                                <label className="text-sm font-medium">End Date</label>
                                <Input
                                    type="date"
                                    value={endDate}
                                    onChange={(e) => setEndDate(e.target.value)}
                                />
                            </div>
                            <Button onClick={fetchShifts}>Filter</Button>
                        </div>

                        {/* Loading State */}
                        {loading && (
                            <div className="flex justify-center py-8">
                                <div className="text-muted-foreground">Loading shifts...</div>
                            </div>
                        )}

                        {/* Shifts List */}
                        {!loading && shifts && (
                            <>
                                <div className="space-y-4">
                                    {shifts.data.map((shift) => (
                                        <Card key={shift.id}>
                                            <CardContent className="p-6">
                                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                                    {/* Basic Info */}
                                                    <div className="space-y-3">
                                                        <h4 className="font-semibold flex items-center gap-2">
                                                            <Calendar className="h-4 w-4" />
                                                            {new Date(shift.shift_date).toLocaleDateString()}
                                                        </h4>
                                                        <div className="space-y-2 text-sm">
                                                            <div>
                                                                <strong>Start:</strong> {formatDateTime(shift.shift_start)}
                                                            </div>
                                                            <div>
                                                                <strong>End:</strong> {formatDateTime(shift.shift_end)}
                                                            </div>
                                                            <div>
                                                                <strong>Duration:</strong> {formatDuration(shift.shift_start, shift.shift_end)}
                                                            </div>
                                                            {shift.total_break_mins && (
                                                                <div>
                                                                    <strong>Break:</strong> {shift.total_break_mins} minutes
                                                                </div>
                                                            )}
                                                        </div>
                                                    </div>

                                                    {/* Location Info */}
                                                    <div className="space-y-3">
                                                        <h4 className="font-semibold flex items-center gap-2">
                                                            <MapPin className="h-4 w-4" />
                                                            Locations
                                                        </h4>
                                                        <div className="space-y-2 text-sm">
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
                                                                <div className="text-muted-foreground">No location data available</div>
                                                            )}
                                                        </div>
                                                    </div>

                                                    {/* Selfies */}
                                                    <div className="space-y-3">
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
                                                                <div className="text-muted-foreground text-sm">No selfies available</div>
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
                                        No shifts found for the selected criteria
                                    </div>
                                )}
                            </>
                        )}
                    </div>
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
