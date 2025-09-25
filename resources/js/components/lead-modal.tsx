import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { type Lead, type LeadFormOptions } from '@/types';
import { useState, useEffect } from 'react';
import axios from 'axios';

interface LeadModalProps {
    isOpen: boolean;
    onClose: () => void;
    onSave: () => void;
    lead: Lead | null; // null for create, Lead object for edit
    formOptions: LeadFormOptions;
}

interface FormData {
    shop_name: string;
    contact_person: string;
    mobile_number: string;
    alternate_number: string;
    email: string;
    address: string;
    branches: number;
    area_locality: string;
    pincode: string;
    gps_location: string;
    business_type: string;
    current_system: string;
    lead_status: string;
    plan_interest: string;
    next_follow_up_date: string;
    meeting_notes: string;
    assigned_to: string;
    action: string;
}

interface FormErrors {
    [key: string]: string[];
}

export default function LeadModal({ isOpen, onClose, onSave, lead, formOptions }: LeadModalProps) {
    const [formData, setFormData] = useState<FormData>({
        shop_name: '',
        contact_person: '',
        mobile_number: '',
        alternate_number: '',
        email: '',
        address: '',
        branches: 1,
        area_locality: '',
        pincode: '',
        gps_location: '',
        business_type: '',
        current_system: '',
        lead_status: '',
        plan_interest: '',
        next_follow_up_date: '',
        meeting_notes: '',
        assigned_to: '',
        action: '',        
    });

    const [errors, setErrors] = useState<FormErrors>({});
    const [loading, setLoading] = useState(false);

    const isEditMode = !!lead;

    // Reset form when modal opens/closes or lead changes
    useEffect(() => {
        if (isOpen) {
            if (lead) {
                // Edit mode - populate form with lead data
                setFormData({
                    shop_name: lead.shop_name || '',
                    contact_person: lead.contact_person || '',
                    mobile_number: lead.mobile_number || '',
                    alternate_number: lead.alternate_number || '',
                    email: lead.email || '',
                    address: lead.address || '',
                    branches: lead.branches || 1,
                    area_locality: lead.area_locality || '',
                    pincode: lead.pincode || '',
                    gps_location: lead.gps_location || '',
                    business_type: lead.business_type?.toString() || '',
                    current_system: lead.current_system?.toString() || '',
                    lead_status: lead.lead_status?.toString() || '',
                    plan_interest: lead.plan_interest || '',
                    next_follow_up_date: lead.next_follow_up_date ? lead.next_follow_up_date.split('T')[0] : '',
                    meeting_notes: lead.meeting_notes || '',
                    assigned_to: lead.assigned_to?.toString() || '',
                    action: '',
                });
            } else {
                // Create mode - reset form
                setFormData({
                    shop_name: '',
                    contact_person: '',
                    mobile_number: '',
                    alternate_number: '',
                    email: '',
                    address: '',
                    branches: 1,
                    area_locality: '',
                    pincode: '',
                    gps_location: '',
                    business_type: '',
                    current_system: '',
                    lead_status: '',
                    plan_interest: '',
                    next_follow_up_date: '',
                    meeting_notes: '',
                    assigned_to: '',
                    action: '',
                });
            }
            setErrors({});
        }
    }, [isOpen, lead]);

    const handleInputChange = (field: keyof FormData, value: string) => {
        setFormData(prev => ({
            ...prev,
            [field]: value
        }));

        // Clear error for this field when user starts typing
        if (errors[field]) {
            setErrors(prev => ({
                ...prev,
                [field]: []
            }));
        }
    };

    const validateForm = (): boolean => {
        const newErrors: FormErrors = {};

        // Required fields start
        if (!formData.shop_name.trim()) {
            newErrors.shop_name = ['Shop name is required'];
        }

        if (!formData.contact_person.trim()) {
            newErrors.contact_person = ['Contact person is required'];
        }

        if (!formData.mobile_number.trim()) {
            newErrors.mobile_number = ['Mobile number is required'];
        }

        if (!formData.address.trim()) {
            newErrors.address = ['Address is required'];
        }

        if (!formData.area_locality.trim()) {
            newErrors.area_locality = ['Area/locality is required'];
        }

        if (!formData.pincode.trim()) {
            newErrors.pincode = ['Pincode is required'];
        }

        if (!formData.gps_location.trim()) {
            newErrors.gps_location = ['GPS location is required'];
        }

        if (!formData.assigned_to.trim()) {
            newErrors.assigned_to = ['Assigned to is required'];
        }

        // if (!formData.business_type.trim() || formData.business_type === 'none') {
        //     newErrors.business_type = ['Business type is required'];
        // }        

        // Required fields end


        // Email validation (optional but if provided should be valid)
        if (formData.email && formData.email.trim()) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(formData.email)) {
                newErrors.email = ['Please enter a valid email address'];
            }
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        if (!validateForm()) {
            return;
        }

        setLoading(true);

        try {
            // Prepare data for submission
            const submitData: any = {
                shop_name: formData.shop_name,
                contact_person: formData.contact_person,
                mobile_number: formData.mobile_number,
                alternate_number: formData.alternate_number || null,
                email: formData.email || null,
                address: formData.address || null,
                branches: formData.branches || null,
                area_locality: formData.area_locality || null,
                pincode: formData.pincode || null,
                gps_location: formData.gps_location || null,
                business_type: formData.business_type && formData.business_type !== 'none' ? parseInt(formData.business_type) : null,
                current_system: formData.current_system && formData.current_system !== 'none' ? parseInt(formData.current_system) : null,
                lead_status: formData.lead_status && formData.lead_status !== 'none' ? parseInt(formData.lead_status) : null,
                plan_interest: formData.plan_interest || null,
                next_follow_up_date: formData.next_follow_up_date || null,
                meeting_notes: formData.meeting_notes || null,
                assigned_to: formData.assigned_to && formData.assigned_to !== 'none' ? parseInt(formData.assigned_to) : null,
                action: formData.action,
            };

            if (isEditMode && lead) {
                // Update existing lead
                await axios.put(`/api/leads/${lead.id}`, submitData);
            } else {
                // Create new lead
                await axios.post('/api/leads', submitData);
            }

            onSave();
        } catch (error: any) {
            console.error('Error saving lead:', error);
            
            // Handle validation errors from server
            if (error.response?.status === 422 && error.response?.data?.errors) {
                setErrors(error.response.data.errors); }
            else if (error.response?.status === 500) {
                setErrors({
                    general: [error.response.data.error || 'A server error occurred. Please try again later.']                    
                });
            } else {
                // Generic error handling
                setErrors({
                    general: ['An error occurred while saving the lead. Please try again. ' + error]                    
                });
            }
        }

        setLoading(false);
    };

    const getErrorMessage = (field: string): string => {
        return errors[field]?.[0] || '';
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-[700px] max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>
                        {isEditMode ? 'Edit Lead' : 'Add New Lead'}
                    </DialogTitle>
                    <DialogDescription>
                        {isEditMode 
                            ? 'Update the lead information below.'
                            : 'Fill in the details to create a new lead.'
                        }
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4">
                    {/* General errors */}
                    {errors.general && (
                        <div className="p-3 text-sm text-red-600 bg-red-50 border border-red-200 rounded-md">
                            {errors.general[0]}
                        </div>
                    )}

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {/* Shop Name */}
                        <div className="space-y-2">
                            <Label htmlFor="shop_name">Shop Name *</Label>
                            <Input
                                id="shop_name"
                                value={formData.shop_name}
                                onChange={(e) => handleInputChange('shop_name', e.target.value)}
                                placeholder="Enter shop name"
                                className={getErrorMessage('shop_name') ? 'border-red-500' : ''}
                            />
                            {getErrorMessage('shop_name') && (
                                <p className="text-sm text-red-600">{getErrorMessage('shop_name')}</p>
                            )}
                        </div>

                        {/* Contact Person */}
                        <div className="space-y-2">
                            <Label htmlFor="contact_person">Contact Person *</Label>
                            <Input
                                id="contact_person"
                                value={formData.contact_person}
                                onChange={(e) => handleInputChange('contact_person', e.target.value)}
                                placeholder="Enter contact person name"
                                className={getErrorMessage('contact_person') ? 'border-red-500' : ''}
                            />
                            {getErrorMessage('contact_person') && (
                                <p className="text-sm text-red-600">{getErrorMessage('contact_person')}</p>
                            )}
                        </div>

                        {/* Mobile Number */}
                        <div className="space-y-2">
                            <Label htmlFor="mobile_number">Mobile Number *</Label>
                            <Input
                                id="mobile_number"
                                value={formData.mobile_number}
                                onChange={(e) => handleInputChange('mobile_number', e.target.value)}
                                placeholder="Enter mobile number"
                                className={getErrorMessage('mobile_number') ? 'border-red-500' : ''}
                            />
                            {getErrorMessage('mobile_number') && (
                                <p className="text-sm text-red-600">{getErrorMessage('mobile_number')}</p>
                            )}
                        </div>

                        {/* Alternate Number */}
                        <div className="space-y-2">
                            <Label htmlFor="alternate_number">Alternate Number</Label>
                            <Input
                                id="alternate_number"
                                value={formData.alternate_number}
                                onChange={(e) => handleInputChange('alternate_number', e.target.value)}
                                placeholder="Enter alternate number"
                                className={getErrorMessage('alternate_number') ? 'border-red-500' : ''}
                            />
                            {getErrorMessage('alternate_number') && (
                                <p className="text-sm text-red-600">{getErrorMessage('alternate_number')}</p>
                            )}
                        </div>

                        {/* Email */}
                        <div className="space-y-2">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                value={formData.email}
                                onChange={(e) => handleInputChange('email', e.target.value)}
                                placeholder="Enter email address"
                                className={getErrorMessage('email') ? 'border-red-500' : ''}
                            />
                            {getErrorMessage('email') && (
                                <p className="text-sm text-red-600">{getErrorMessage('email')}</p>
                            )}
                        </div>

                        {/* Branches */}

                        <div className="space-y-2">
                            <Label htmlFor="branches">Branches</Label>
                            <Input
                                id="branches"
                                value={formData.branches}
                                onChange={(e) => handleInputChange('branches', e.target.value)}
                                placeholder="Enter branches"
                                className={getErrorMessage('branches') ? 'border-red-500' : ''}
                            />
                            {getErrorMessage('branches') && (
                                <p className="text-sm text-red-600">{getErrorMessage('branches')}</p>
                            )}
                        </div>

                        {/* Area/Locality */}
                        <div className="space-y-2">
                            <Label htmlFor="area_locality">Area/Locality *</Label>
                            <Input
                                id="area_locality"
                                value={formData.area_locality}
                                onChange={(e) => handleInputChange('area_locality', e.target.value)}
                                placeholder="Enter area or locality"
                                className={getErrorMessage('area_locality') ? 'border-red-500' : ''}
                            />
                            {getErrorMessage('area_locality') && (
                                <p className="text-sm text-red-600">{getErrorMessage('area_locality')}</p>
                            )}
                        </div>

                        {/* Pincode */}
                        <div className="space-y-2">
                            <Label htmlFor="pincode">Pincode *</Label>
                            <Input
                                id="pincode"
                                value={formData.pincode}
                                onChange={(e) => handleInputChange('pincode', e.target.value)}
                                placeholder="Enter pincode"
                                className={getErrorMessage('pincode') ? 'border-red-500' : ''}
                            />
                            {getErrorMessage('pincode') && (
                                <p className="text-sm text-red-600">{getErrorMessage('pincode')}</p>
                            )}
                        </div>

                        {/* GPS Location */}
                        <div className="space-y-2">
                            <Label htmlFor="gps_location">GPS Location *</Label>
                            <Input
                                id="gps_location"
                                value={formData.gps_location}
                                onChange={(e) => handleInputChange('gps_location', e.target.value)}
                                placeholder="Enter GPS coordinates (latitude, longitude)"
                                className={getErrorMessage('gps_location') ? 'border-red-500' : ''}
                            />
                            {getErrorMessage('gps_location') && (
                                <p className="text-sm text-red-600">{getErrorMessage('gps_location')}</p>
                            )}
                        </div>

                        {/* Business Type */}
                        <div className="space-y-2">
                            <Label htmlFor="business_type">Business Type</Label>
                            <Select value={formData.business_type} onValueChange={(value) => handleInputChange('business_type', value)}>
                                <SelectTrigger className={getErrorMessage('business_type') ? 'border-red-500' : ''}>
                                    <SelectValue placeholder="Select business type" />
                                </SelectTrigger>
                                <SelectContent>
                                    {/* <SelectItem value="none">No business type</SelectItem> */}
                                    {formOptions.business_types.map((type) => (
                                        <SelectItem key={type.id} value={type.id.toString()}>
                                            {type.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {getErrorMessage('business_type') && (
                                <p className="text-sm text-red-600">{getErrorMessage('business_type')}</p>
                            )}
                        </div>
                        

                        {/* Current System */}
                        <div className="space-y-2">
                            <Label htmlFor="current_system">Current System</Label>
                            <Select value={formData.current_system} onValueChange={(value) => handleInputChange('current_system', value)}>
                                <SelectTrigger className={getErrorMessage('current_system') ? 'border-red-500' : ''}>
                                    <SelectValue placeholder="Select current system" />
                                </SelectTrigger>
                                <SelectContent>
                                    {/* <SelectItem value="none">No current system</SelectItem> */}
                                    {formOptions.current_systems.map((system) => (
                                        <SelectItem key={system.id} value={system.id.toString()}>
                                            {system.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {getErrorMessage('current_system') && (
                                <p className="text-sm text-red-600">{getErrorMessage('current_system')}</p>
                            )}
                        </div>

                        {/* Lead Status */}
                        <div className="space-y-2">
                            <Label htmlFor="lead_status">Lead Status</Label>
                            <Select value={formData.lead_status} onValueChange={(value) => handleInputChange('lead_status', value)}>
                                <SelectTrigger className={getErrorMessage('lead_status') ? 'border-red-500' : ''}>
                                    <SelectValue placeholder="Select lead status" />
                                </SelectTrigger>
                                <SelectContent>
                                    {/* <SelectItem value="none">No status</SelectItem> */}
                                    {formOptions.lead_statuses.map((status) => (
                                        <SelectItem key={status.id} value={status.id.toString()}>
                                            {status.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {getErrorMessage('lead_status') && (
                                <p className="text-sm text-red-600">{getErrorMessage('lead_status')}</p>
                            )}
                        </div>

                        {/* Assigned To */}
                        <div className="space-y-2">
                            <Label htmlFor="assigned_to">Assigned To *</Label>
                            <Select value={formData.assigned_to} onValueChange={(value) => handleInputChange('assigned_to', value)}>
                                <SelectTrigger className={getErrorMessage('assigned_to') ? 'border-red-500' : ''}>
                                    <SelectValue placeholder="Select user" />
                                </SelectTrigger>
                                <SelectContent>
                                    {formOptions.users.map((user) => (
                                        <SelectItem key={user.id} value={user.id.toString()}>
                                            {user.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {getErrorMessage('assigned_to') && (
                                <p className="text-sm text-red-600">{getErrorMessage('assigned_to')}</p>
                            )}
                        </div>
                        
                        {/* Assigned To */}
                        <div className="space-y-2">
                            <Label htmlFor="action">Action</Label>
                            <Select value={formData.action} onValueChange={(value) => handleInputChange('action', value)}>
                                <SelectTrigger className={getErrorMessage('action') ? 'border-red-500' : ''}>
                                    <SelectValue placeholder="Select your action" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="Meet">Meet</SelectItem>
                                    <SelectItem value="Call">Call</SelectItem>
                                    <SelectItem value="Update">Update</SelectItem>                                                                    
                                </SelectContent>
                            </Select>
                            {getErrorMessage('action') && (
                                <p className="text-sm text-red-600">{getErrorMessage('action')}</p>
                            )}
                        </div>                        

                        {/* Next Follow-up Date */}
                        <div className="space-y-2">
                            <Label htmlFor="next_follow_up_date">Next Follow-up Date</Label>
                            <Input
                                id="next_follow_up_date"
                                type="date"
                                value={formData.next_follow_up_date}
                                onChange={(e) => handleInputChange('next_follow_up_date', e.target.value)}
                                className={getErrorMessage('next_follow_up_date') ? 'border-red-500' : ''}
                            />
                            {getErrorMessage('next_follow_up_date') && (
                                <p className="text-sm text-red-600">{getErrorMessage('next_follow_up_date')}</p>
                            )}
                        </div>
                    </div>

                    {/* Address */}
                    <div className="space-y-2">
                        <Label htmlFor="address">Address *</Label>
                        <Textarea
                            id="address"
                            value={formData.address}
                            onChange={(e) => handleInputChange('address', e.target.value)}
                            placeholder="Enter full address"
                            className={getErrorMessage('address') ? 'border-red-500' : ''}
                            rows={2}
                        />
                        {getErrorMessage('address') && (
                            <p className="text-sm text-red-600">{getErrorMessage('address')}</p>
                        )}
                    </div>

                    {/* Plan Interest */}
                    <div className="space-y-2">
                        <Label htmlFor="plan_interest">Plan Interest</Label>
                        <Input
                            id="plan_interest"
                            value={formData.plan_interest}
                            onChange={(e) => handleInputChange('plan_interest', e.target.value)}
                            placeholder="Enter plan interest details"
                            className={getErrorMessage('plan_interest') ? 'border-red-500' : ''}
                        />
                        {getErrorMessage('plan_interest') && (
                            <p className="text-sm text-red-600">{getErrorMessage('plan_interest')}</p>
                        )}
                    </div>

                    {/* Meeting Notes */}
                    <div className="space-y-2">
                        <Label htmlFor="meeting_notes">Meeting Notes</Label>
                        <Textarea
                            id="meeting_notes"
                            value={formData.meeting_notes}
                            onChange={(e) => handleInputChange('meeting_notes', e.target.value)}
                            placeholder="Enter meeting notes"
                            className={getErrorMessage('meeting_notes') ? 'border-red-500' : ''}
                            rows={3}
                        />
                        {getErrorMessage('meeting_notes') && (
                            <p className="text-sm text-red-600">{getErrorMessage('meeting_notes')}</p>
                        )}
                    </div>

                    <DialogFooter className="gap-2">
                        <Button type="button" variant="outline" onClick={onClose} disabled={loading}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={loading}>
                            {loading ? 'Saving...' : (isEditMode ? 'Update Lead' : 'Create Lead')}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

