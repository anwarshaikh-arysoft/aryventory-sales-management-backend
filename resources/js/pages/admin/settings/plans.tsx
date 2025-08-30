import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import DataTable from '@/components/data-table';
import CrudModal from '@/components/crud-modal';
import DeleteConfirmationModal from '@/components/delete-confirmation-modal';
import { Plan } from '@/types';
import { useState } from 'react';

const columns = [
    { key: 'id', label: 'ID', type: 'number' as const },
    { key: 'name', label: 'Name', type: 'text' as const },
    { key: 'interval', label: 'Interval', type: 'badge' as const },
    { key: 'amount', label: 'Amount', type: 'currency' as const },
    { key: 'status', label: 'Status', type: 'badge' as const },
    { key: 'created_at', label: 'Created', type: 'date' as const },
];

const formFields = [
    { 
        name: 'name', 
        label: 'Plan Name', 
        type: 'text' as const, 
        required: true, 
        placeholder: 'Enter plan name' 
    },
    { 
        name: 'interval', 
        label: 'Billing Interval', 
        type: 'select' as const, 
        required: true,
        options: [
            { value: 'monthly', label: 'Monthly' },
            { value: 'quarterly', label: 'Quarterly' },
            { value: 'yearly', label: 'Yearly' },
        ]
    },
    { 
        name: 'amount', 
        label: 'Amount', 
        type: 'number' as const, 
        required: true, 
        placeholder: 'Enter amount' 
    },
    { 
        name: 'status', 
        label: 'Status', 
        type: 'select' as const, 
        required: true,
        options: [
            { value: 'active', label: 'Active' },
            { value: 'inactive', label: 'Inactive' },
            { value: 'deprecated', label: 'Deprecated' },
        ]
    },
];

export default function PlansPage() {
    const [showAddModal, setShowAddModal] = useState(false);
    const [showEditModal, setShowEditModal] = useState(false);
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [selectedItem, setSelectedItem] = useState<Plan | null>(null);
    const [refreshTrigger, setRefreshTrigger] = useState(0);

    const handleAdd = () => {
        setSelectedItem(null);
        setShowAddModal(true);
    };

    const handleEdit = (item: Plan) => {
        setSelectedItem(item);
        setShowEditModal(true);
    };

    const handleDelete = (item: Plan) => {
        setSelectedItem(item);
        setShowDeleteModal(true);
    };

    const handleModalSave = () => {
        setRefreshTrigger(prev => prev + 1);
        setShowAddModal(false);
        setShowEditModal(false);
    };

    const handleDeleteConfirm = () => {
        setRefreshTrigger(prev => prev + 1);
        setShowDeleteModal(false);
    };

    return (
        <AppLayout>
            <SettingsLayout>
                <DataTable
                    title="Plans"
                    description="Manage subscription plans and pricing"
                    apiEndpoint="/api/plans"
                    columns={columns}
                    onAdd={handleAdd}
                    onEdit={handleEdit}
                    onDelete={handleDelete}
                    searchPlaceholder="Search plans..."
                    refreshTrigger={refreshTrigger}
                />

                <CrudModal
                    isOpen={showAddModal}
                    onClose={() => setShowAddModal(false)}
                    onSave={handleModalSave}
                    title="Plan"
                    description="Add a new plan"
                    apiEndpoint="/api/plans"
                    fields={formFields}
                    item={null}
                />

                <CrudModal
                    isOpen={showEditModal}
                    onClose={() => setShowEditModal(false)}
                    onSave={handleModalSave}
                    title="Plan"
                    description="Edit plan details"
                    apiEndpoint="/api/plans"
                    fields={formFields}
                    item={selectedItem}
                />

                <DeleteConfirmationModal
                    isOpen={showDeleteModal}
                    onClose={() => setShowDeleteModal(false)}
                    onDeleted={handleDeleteConfirm}
                    title="Plan"
                    apiEndpoint="/api/plans"
                    item={selectedItem}
                    itemDisplayName="name"
                />
            </SettingsLayout>
        </AppLayout>
    );
}
