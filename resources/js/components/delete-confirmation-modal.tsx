import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { AlertTriangle } from 'lucide-react';
import { useState } from 'react';
import axios from 'axios';

interface DeleteConfirmationModalProps {
    isOpen: boolean;
    onClose: () => void;
    onDeleted: () => void;
    title: string;
    description?: string;
    apiEndpoint: string;
    item: any | null;
    itemDisplayName?: string; // Field name to display from item (e.g., 'name')
}

export default function DeleteConfirmationModal({
    isOpen,
    onClose,
    onDeleted,
    title,
    description,
    apiEndpoint,
    item,
    itemDisplayName = 'name'
}: DeleteConfirmationModalProps) {
    const [isLoading, setIsLoading] = useState(false);

    const handleDelete = async () => {
        if (!item) return;

        setIsLoading(true);
        try {
            await axios.delete(`${apiEndpoint}/${item.id}`);
            onDeleted();
            onClose();
        } catch (error: any) {
            console.error('Error deleting item:', error);
            // You could show a toast notification here
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-[425px]">
                <DialogHeader>
                    <div className="flex items-center space-x-2">
                        <AlertTriangle className="h-5 w-5 text-red-500" />
                        <DialogTitle>Delete {title}</DialogTitle>
                    </div>
                    <DialogDescription>
                        {description || `Are you sure you want to delete "${item?.[itemDisplayName]}"? This action cannot be undone.`}
                    </DialogDescription>
                </DialogHeader>
                
                <DialogFooter>
                    <Button type="button" variant="outline" onClick={onClose}>
                        Cancel
                    </Button>
                    <Button 
                        type="button" 
                        variant="destructive" 
                        onClick={handleDelete}
                        disabled={isLoading}
                    >
                        {isLoading ? 'Deleting...' : 'Delete'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
