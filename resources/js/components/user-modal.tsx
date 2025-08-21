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
import { type Role, type Group, type User } from '@/types';
import { useState, useEffect } from 'react';
import axios from 'axios';

interface UserModalProps {
    isOpen: boolean;
    onClose: () => void;
    onSave: () => void;
    user: User | null; // null for create, User object for edit
    roles: Role[];
    groups: Group[];
}

interface FormData {
    name: string;
    email: string;
    phone: string;
    designation: string;
    role_id: string;
    group_id: string;
    password: string;
    password_confirmation: string;
}

interface FormErrors {
    [key: string]: string[];
}

export default function UserModal({ isOpen, onClose, onSave, user, roles, groups }: UserModalProps) {
    const [formData, setFormData] = useState<FormData>({
        name: '',
        email: '',
        phone: '',
        designation: '',
        role_id: '',
        group_id: '',
        password: '',
        password_confirmation: '',
    });

    const [errors, setErrors] = useState<FormErrors>({});
    const [loading, setLoading] = useState(false);

    const isEditMode = !!user;

    // Reset form when modal opens/closes or user changes
    useEffect(() => {
        if (isOpen) {
            if (user) {
                // Edit mode - populate form with user data
                setFormData({
                    name: user.name || '',
                    email: user.email || '',
                    phone: user.phone || '',
                    designation: user.designation || '',
                    role_id: user.role_id?.toString() || '',
                    group_id: user.group_id?.toString() || '',
                    password: '',
                    password_confirmation: '',
                });
            } else {
                // Create mode - reset form
                setFormData({
                    name: '',
                    email: '',
                    phone: '',
                    designation: '',
                    role_id: '',
                    group_id: '',
                    password: '',
                    password_confirmation: '',
                });
            }
            setErrors({});
        }
    }, [isOpen, user]);

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

        // Required fields
        if (!formData.name.trim()) {
            newErrors.name = ['Name is required'];
        }

        if (!formData.email.trim()) {
            newErrors.email = ['Email is required'];
        } else {
            // Basic email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(formData.email)) {
                newErrors.email = ['Please enter a valid email address'];
            }
        }

        // Password validation (required for create, optional for edit)
        if (!isEditMode) {
            if (!formData.password) {
                newErrors.password = ['Password is required'];
            } else if (formData.password.length < 6) {
                newErrors.password = ['Password must be at least 6 characters'];
            }

            if (formData.password !== formData.password_confirmation) {
                newErrors.password_confirmation = ['Passwords do not match'];
            }
        } else {
            // For edit mode, if password is provided, validate it
            if (formData.password && formData.password.length < 6) {
                newErrors.password = ['Password must be at least 6 characters'];
            }

            if (formData.password && formData.password !== formData.password_confirmation) {
                newErrors.password_confirmation = ['Passwords do not match'];
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
                name: formData.name,
                email: formData.email,
                phone: formData.phone || null,
                designation: formData.designation || null,
                role_id: formData.role_id && formData.role_id !== 'none' ? parseInt(formData.role_id) : null,
                group_id: formData.group_id && formData.group_id !== 'none' ? parseInt(formData.group_id) : null,
            };

            // Add password only if provided
            if (formData.password) {
                submitData.password = formData.password;
            }

            if (isEditMode && user) {
                // Update existing user
                await axios.put(`/api/users/${user.id}`, submitData);
            } else {
                // Create new user
                await axios.post('/api/users', submitData);
            }

            onSave();
        } catch (error: any) {
            console.error('Error saving user:', error);
            
            // Handle validation errors from server
            if (error.response?.status === 422 && error.response?.data?.errors) {
                setErrors(error.response.data.errors);
            } else {
                // Generic error handling
                setErrors({
                    general: ['An error occurred while saving the user. Please try again.']
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
            <DialogContent className="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle>
                        {isEditMode ? 'Edit User' : 'Add New User'}
                    </DialogTitle>
                    <DialogDescription>
                        {isEditMode 
                            ? 'Update the user information below.'
                            : 'Fill in the details to create a new user account.'
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

                    {/* Name */}
                    <div className="space-y-2">
                        <Label htmlFor="name">Name *</Label>
                        <Input
                            id="name"
                            value={formData.name}
                            onChange={(e) => handleInputChange('name', e.target.value)}
                            placeholder="Enter full name"
                            className={getErrorMessage('name') ? 'border-red-500' : ''}
                        />
                        {getErrorMessage('name') && (
                            <p className="text-sm text-red-600">{getErrorMessage('name')}</p>
                        )}
                    </div>

                    {/* Email */}
                    <div className="space-y-2">
                        <Label htmlFor="email">Email *</Label>
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

                    {/* Phone */}
                    <div className="space-y-2">
                        <Label htmlFor="phone">Phone</Label>
                        <Input
                            id="phone"
                            value={formData.phone}
                            onChange={(e) => handleInputChange('phone', e.target.value)}
                            placeholder="Enter phone number"
                            className={getErrorMessage('phone') ? 'border-red-500' : ''}
                        />
                        {getErrorMessage('phone') && (
                            <p className="text-sm text-red-600">{getErrorMessage('phone')}</p>
                        )}
                    </div>

                    {/* Designation */}
                    <div className="space-y-2">
                        <Label htmlFor="designation">Designation</Label>
                        <Input
                            id="designation"
                            value={formData.designation}
                            onChange={(e) => handleInputChange('designation', e.target.value)}
                            placeholder="Enter job designation"
                            className={getErrorMessage('designation') ? 'border-red-500' : ''}
                        />
                        {getErrorMessage('designation') && (
                            <p className="text-sm text-red-600">{getErrorMessage('designation')}</p>
                        )}
                    </div>

                    {/* Role */}
                    <div className="space-y-2">
                        <Label htmlFor="role">Role</Label>
                        <Select value={formData.role_id} onValueChange={(value) => handleInputChange('role_id', value)}>
                            <SelectTrigger className={getErrorMessage('role_id') ? 'border-red-500' : ''}>
                                <SelectValue placeholder="Select a role" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">No role</SelectItem>
                                {roles.map((role) => (
                                    <SelectItem key={role.id} value={role.id.toString()}>
                                        {role.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {getErrorMessage('role_id') && (
                            <p className="text-sm text-red-600">{getErrorMessage('role_id')}</p>
                        )}
                    </div>

                    {/* Group */}
                    <div className="space-y-2">
                        <Label htmlFor="group">Group</Label>
                        <Select value={formData.group_id} onValueChange={(value) => handleInputChange('group_id', value)}>
                            <SelectTrigger className={getErrorMessage('group_id') ? 'border-red-500' : ''}>
                                <SelectValue placeholder="Select a group" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">No group</SelectItem>
                                {groups.map((group) => (
                                    <SelectItem key={group.id} value={group.id.toString()}>
                                        {group.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {getErrorMessage('group_id') && (
                            <p className="text-sm text-red-600">{getErrorMessage('group_id')}</p>
                        )}
                    </div>

                    {/* Password */}
                    <div className="space-y-2">
                        <Label htmlFor="password">
                            Password {!isEditMode && '*'}
                            {isEditMode && (
                                <span className="text-sm text-muted-foreground ml-1">
                                    (leave blank to keep current password)
                                </span>
                            )}
                        </Label>
                        <Input
                            id="password"
                            type="password"
                            value={formData.password}
                            onChange={(e) => handleInputChange('password', e.target.value)}
                            placeholder="Enter password"
                            className={getErrorMessage('password') ? 'border-red-500' : ''}
                        />
                        {getErrorMessage('password') && (
                            <p className="text-sm text-red-600">{getErrorMessage('password')}</p>
                        )}
                    </div>

                    {/* Confirm Password */}
                    {(formData.password || !isEditMode) && (
                        <div className="space-y-2">
                            <Label htmlFor="password_confirmation">Confirm Password *</Label>
                            <Input
                                id="password_confirmation"
                                type="password"
                                value={formData.password_confirmation}
                                onChange={(e) => handleInputChange('password_confirmation', e.target.value)}
                                placeholder="Confirm password"
                                className={getErrorMessage('password_confirmation') ? 'border-red-500' : ''}
                            />
                            {getErrorMessage('password_confirmation') && (
                                <p className="text-sm text-red-600">{getErrorMessage('password_confirmation')}</p>
                            )}
                        </div>
                    )}

                    <DialogFooter className="gap-2">
                        <Button type="button" variant="outline" onClick={onClose} disabled={loading}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={loading}>
                            {loading ? 'Saving...' : (isEditMode ? 'Update User' : 'Create User')}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
