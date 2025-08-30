import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Plus, Search, Edit, Trash2 } from 'lucide-react';
import { useState, useEffect } from 'react';
import axios from 'axios';
import { PaginatedResponse } from '@/types';

interface Column {
    key: string;
    label: string;
    type?: 'text' | 'badge' | 'date' | 'number' | 'currency';
    render?: (value: any, item: any) => React.ReactNode;
}

interface DataTableProps {
    title: string;
    description?: string;
    apiEndpoint: string;
    columns: Column[];
    onAdd: () => void;
    onEdit: (item: any) => void;
    onDelete: (item: any) => void;
    searchPlaceholder?: string;
    refreshTrigger?: number; // Used to trigger refresh from parent
}

export default function DataTable({
    title,
    description,
    apiEndpoint,
    columns,
    onAdd,
    onEdit,
    onDelete,
    searchPlaceholder = "Search...",
    refreshTrigger = 0
}: DataTableProps) {
    const [data, setData] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const [searchQuery, setSearchQuery] = useState('');
    const [currentPage, setCurrentPage] = useState(1);
    const [pagination, setPagination] = useState<any>(null);

    const fetchData = async (page = 1, search = '') => {
        try {
            setLoading(true);
            const params = new URLSearchParams({
                page: page.toString(),
                per_page: '15',
                ...(search && { q: search })
            });
            
            const response = await axios.get(`${apiEndpoint}?${params}`);
            const result = response.data;
            
            // Handle both paginated and non-paginated responses
            if (result.data && Array.isArray(result.data)) {
                // Paginated response
                setData(result.data);
                setPagination({
                    current_page: result.current_page,
                    last_page: result.last_page,
                    total: result.total,
                    per_page: result.per_page,
                    from: result.from,
                    to: result.to
                });
            } else if (Array.isArray(result)) {
                // Non-paginated array response
                setData(result);
                setPagination(null);
            } else {
                // Fallback
                setData([]);
                setPagination(null);
            }
        } catch (error) {
            console.error('Error fetching data:', error);
            setData([]);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchData(1, searchQuery);
    }, [apiEndpoint, refreshTrigger]);

    useEffect(() => {
        const delayedSearch = setTimeout(() => {
            fetchData(1, searchQuery);
            setCurrentPage(1);
        }, 300);

        return () => clearTimeout(delayedSearch);
    }, [searchQuery]);

    const handlePageChange = (page: number) => {
        setCurrentPage(page);
        fetchData(page, searchQuery);
    };

    const renderCellValue = (column: Column, value: any, item: any) => {
        if (column.render) {
            return column.render(value, item);
        }

        // Handle nested values like 'user.name'
        if (column.key.includes('.')) {
            const keys = column.key.split('.');
            let nestedValue = item;
            for (const key of keys) {
                nestedValue = nestedValue?.[key];
                if (nestedValue === undefined || nestedValue === null) break;
            }
            value = nestedValue;
        }

        switch (column.type) {
            case 'badge':
                return <Badge variant="secondary">{value}</Badge>;
            case 'date':
                return value ? new Date(value).toLocaleDateString() : '-';
            case 'number':
                return value?.toLocaleString() || '0';
            case 'currency':
                return value ? `$${Number(value).toLocaleString()}` : '$0';
            default:
                return value || '-';
        }
    };

    const renderPagination = () => {
        if (!pagination || pagination.last_page <= 1) return null;

        const pages = [];
        const showPages = 5;
        const half = Math.floor(showPages / 2);
        
        let start = Math.max(1, pagination.current_page - half);
        let end = Math.min(pagination.last_page, start + showPages - 1);
        
        if (end - start + 1 < showPages) {
            start = Math.max(1, end - showPages + 1);
        }

        for (let i = start; i <= end; i++) {
            pages.push(i);
        }

        return (
            <div className="flex items-center justify-between px-2">
                <div className="text-sm text-muted-foreground">
                    Showing {pagination.from} to {pagination.to} of {pagination.total} results
                </div>
                <div className="flex space-x-1">
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => handlePageChange(pagination.current_page - 1)}
                        disabled={pagination.current_page <= 1}
                    >
                        Previous
                    </Button>
                    {pages.map(page => (
                        <Button
                            key={page}
                            variant={page === pagination.current_page ? "default" : "outline"}
                            size="sm"
                            onClick={() => handlePageChange(page)}
                        >
                            {page}
                        </Button>
                    ))}
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => handlePageChange(pagination.current_page + 1)}
                        disabled={pagination.current_page >= pagination.last_page}
                    >
                        Next
                    </Button>
                </div>
            </div>
        );
    };

    return (
        <Card>
            <CardHeader>
                <div className="flex items-center justify-between">
                    <div>
                        <CardTitle>{title}</CardTitle>
                        {description && <CardDescription>{description}</CardDescription>}
                    </div>
                    <Button onClick={onAdd}>
                        <Plus className="h-4 w-4 mr-2" />
                        Add {title.slice(0, -1)}
                    </Button>
                </div>
                <div className="flex items-center space-x-2">
                    <div className="relative flex-1 max-w-sm">
                        <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
                        <Input
                            placeholder={searchPlaceholder}
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            className="pl-10"
                        />
                    </div>
                </div>
            </CardHeader>
            <CardContent>
                {loading ? (
                    <div className="flex justify-center py-8">
                        <div className="text-muted-foreground">Loading...</div>
                    </div>
                ) : (
                    <>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    {columns.map((column) => (
                                        <TableHead key={column.key}>{column.label}</TableHead>
                                    ))}
                                    <TableHead className="w-[100px]">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {data.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={columns.length + 1} className="text-center py-8">
                                            No data found
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    data.map((item) => (
                                        <TableRow key={item.id}>
                                            {columns.map((column) => (
                                                <TableCell key={column.key}>
                                                    {renderCellValue(column, item[column.key], item)}
                                                </TableCell>
                                            ))}
                                            <TableCell>
                                                <div className="flex space-x-2">
                                                    <Button
                                                        size="sm"
                                                        variant="ghost"
                                                        onClick={() => onEdit(item)}
                                                    >
                                                        <Edit className="h-4 w-4" />
                                                    </Button>
                                                    <Button
                                                        size="sm"
                                                        variant="ghost"
                                                        onClick={() => onDelete(item)}
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                        {renderPagination()}
                    </>
                )}
            </CardContent>
        </Card>
    );
}
