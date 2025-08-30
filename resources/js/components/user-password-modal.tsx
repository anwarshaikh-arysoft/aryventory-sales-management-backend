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
import { type User } from '@/types';
import { useState, useEffect } from 'react';
import axios from 'axios';

interface UserPasswordModalProps {
    isOpen: boolean;
    onClose: () => void;
    onSuccess: () => void;
    user: User | null;
}

interface FormData {
    password: string;
    password_confirmation: string;
}

interface FormErrors {
    [key: string]: string[];
}

export default function UserPasswordModal({ isOpen, onClose, onSuccess, user }: UserPasswordModalProps) {
    const [formData, setFormData] = useState<FormData>({
        password: '',
        password_confirmation: '',
    });

    const [errors, setErrors] = useState<FormErrors>({});
    const [loading, setLoading] = useState(false);

    // Reset form when modal opens/closes
    useEffect(() => {
        if (isOpen) {
            setFormData({
                password: '',
                password_confirmation: '',
            });
            setErrors({});
        }
    }, [isOpen]);

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

        // Password validation
        if (!formData.password) {
            newErrors.password = ['Password is required'];
        } else if (formData.password.length < 6) {
            newErrors.password = ['Password must be at least 6 characters'];
        }

        if (formData.password !== formData.password_confirmation) {
            newErrors.password_confirmation = ['Passwords do not match'];
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        if (!user) return;

        if (!validateForm()) {
            return;
        }

        setLoading(true);

        try {
            // Send only password data
            await axios.put(`/api/users/${user.id}/password`, {
                password: formData.password,
            });

            onSuccess();
            onClose();
        } catch (error: any) {
            console.error('Error updating password:', error);
            
            // Handle validation errors from server
            if (error.response?.status === 422 && error.response?.data?.errors) {
                setErrors(error.response.data.errors);
            } else {
                // Generic error handling
                setErrors({
                    general: ['An error occurred while updating the password. Please try again.']
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
            <DialogContent className="sm:max-w-[400px]">
                <DialogHeader>
                    <DialogTitle>Change Password</DialogTitle>
                    <DialogDescription>
                        Change the password for {user?.name}. The user will need to use the new password to log in.
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4">
                    {/* General errors */}
                    {errors.general && (
                        <div className="p-3 text-sm text-red-600 bg-red-50 border border-red-200 rounded-md">
                            {errors.general[0]}
                        </div>
                    )}

                    {/* New Password */}
                    <div className="space-y-2">
                        <Label htmlFor="password">New Password *</Label>
                        <Input
                            id="password"
                            type="password"
                            value={formData.password}
                            onChange={(e) => handleInputChange('password', e.target.value)}
                            placeholder="Enter new password"
                            className={getErrorMessage('password') ? 'border-red-500' : ''}
                        />
                        {getErrorMessage('password') && (
                            <p className="text-sm text-red-600">{getErrorMessage('password')}</p>
                        )}
                    </div>

                    {/* Confirm Password */}
                    <div className="space-y-2">
                        <Label htmlFor="password_confirmation">Confirm Password *</Label>
                        <Input
                            id="password_confirmation"
                            type="password"
                            value={formData.password_confirmation}
                            onChange={(e) => handleInputChange('password_confirmation', e.target.value)}
                            placeholder="Confirm new password"
                            className={getErrorMessage('password_confirmation') ? 'border-red-500' : ''}
                        />
                        {getErrorMessage('password_confirmation') && (
                            <p className="text-sm text-red-600">{getErrorMessage('password_confirmation')}</p>
                        )}
                    </div>

                    <DialogFooter className="gap-2">
                        <Button type="button" variant="outline" onClick={onClose} disabled={loading}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={loading}>
                            {loading ? 'Updating...' : 'Update Password'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
