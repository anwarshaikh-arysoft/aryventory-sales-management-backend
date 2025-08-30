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
import { useState, useEffect } from 'react';
import axios from 'axios';

interface FormField {
    name: string;
    label: string;
    type: 'text' | 'number' | 'select';
    required?: boolean;
    options?: Array<{ value: string | number; label: string }>;
    placeholder?: string;
}

interface CrudModalProps {
    isOpen: boolean;
    onClose: () => void;
    onSave: () => void;
    title: string;
    description?: string;
    apiEndpoint: string;
    fields: FormField[];
    item: any | null; // null for create, object for edit
}

interface FormErrors {
    [key: string]: string[];
}

export default function CrudModal({ 
    isOpen, 
    onClose, 
    onSave, 
    title, 
    description, 
    apiEndpoint, 
    fields, 
    item 
}: CrudModalProps) {
    const [formData, setFormData] = useState<{ [key: string]: any }>({});
    const [errors, setErrors] = useState<FormErrors>({});
    const [isLoading, setIsLoading] = useState(false);
    const isEditMode = item !== null;

    // Initialize form data
    useEffect(() => {
        if (isOpen) {
            if (isEditMode && item) {
                // Edit mode: populate with existing data
                setFormData(item);
            } else {
                // Create mode: reset to default values
                const defaultData: { [key: string]: any } = {};
                fields.forEach(field => {
                    defaultData[field.name] = field.type === 'number' ? 0 : '';
                });
                setFormData(defaultData);
            }
            setErrors({});
        }
    }, [isOpen, item, isEditMode, fields]);

    const handleInputChange = (name: string, value: any) => {
        setFormData(prev => ({
            ...prev,
            [name]: value
        }));
        
        // Clear error for this field
        if (errors[name]) {
            setErrors(prev => ({
                ...prev,
                [name]: []
            }));
        }
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsLoading(true);
        setErrors({});

        try {
            const url = isEditMode ? `${apiEndpoint}/${item.id}` : apiEndpoint;
            const method = isEditMode ? 'put' : 'post';
            
            await axios[method](url, formData);
            
            onSave();
            onClose();
        } catch (error: any) {
            if (error.response?.status === 422) {
                setErrors(error.response.data.errors || {});
            } else {
                console.error('Error saving data:', error);
                // You could show a toast notification here
            }
        } finally {
            setIsLoading(false);
        }
    };

    const renderField = (field: FormField) => {
        const fieldErrors = errors[field.name] || [];
        const hasError = fieldErrors.length > 0;

        switch (field.type) {
            case 'select':
                return (
                    <div key={field.name} className="space-y-2">
                        <Label htmlFor={field.name}>
                            {field.label}
                            {field.required && <span className="text-red-500 ml-1">*</span>}
                        </Label>
                        <Select
                            value={formData[field.name]?.toString() || ''}
                            onValueChange={(value) => handleInputChange(field.name, field.type === 'number' ? Number(value) : value)}
                        >
                            <SelectTrigger className={hasError ? "border-red-500" : ""}>
                                <SelectValue placeholder={field.placeholder || `Select ${field.label.toLowerCase()}`} />
                            </SelectTrigger>
                            <SelectContent>
                                {field.options?.map((option) => (
                                    <SelectItem key={option.value} value={option.value.toString()}>
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {hasError && (
                            <p className="text-sm text-red-500">{fieldErrors[0]}</p>
                        )}
                    </div>
                );

            case 'number':
                return (
                    <div key={field.name} className="space-y-2">
                        <Label htmlFor={field.name}>
                            {field.label}
                            {field.required && <span className="text-red-500 ml-1">*</span>}
                        </Label>
                        <Input
                            id={field.name}
                            type="number"
                            placeholder={field.placeholder}
                            value={formData[field.name] || ''}
                            onChange={(e) => handleInputChange(field.name, Number(e.target.value))}
                            className={hasError ? "border-red-500" : ""}
                        />
                        {hasError && (
                            <p className="text-sm text-red-500">{fieldErrors[0]}</p>
                        )}
                    </div>
                );

            default: // text
                return (
                    <div key={field.name} className="space-y-2">
                        <Label htmlFor={field.name}>
                            {field.label}
                            {field.required && <span className="text-red-500 ml-1">*</span>}
                        </Label>
                        <Input
                            id={field.name}
                            type="text"
                            placeholder={field.placeholder}
                            value={formData[field.name] || ''}
                            onChange={(e) => handleInputChange(field.name, e.target.value)}
                            className={hasError ? "border-red-500" : ""}
                        />
                        {hasError && (
                            <p className="text-sm text-red-500">{fieldErrors[0]}</p>
                        )}
                    </div>
                );
        }
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>{isEditMode ? `Edit ${title}` : `Add ${title}`}</DialogTitle>
                    {description && <DialogDescription>{description}</DialogDescription>}
                </DialogHeader>
                
                <form onSubmit={handleSubmit} className="space-y-4">
                    {fields.map(renderField)}
                    
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={onClose}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={isLoading}>
                            {isLoading ? 'Saving...' : (isEditMode ? 'Update' : 'Create')}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
