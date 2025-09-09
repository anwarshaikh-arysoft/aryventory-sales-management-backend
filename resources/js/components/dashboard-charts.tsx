import React from 'react';
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer,
  PieChart,
  Pie,
  Cell,
  LineChart,
  Line,
  Area,
  AreaChart,
} from 'recharts';

// Revenue Chart Component
interface RevenueData {
  name: string;
  completed_leads: number;
  estimated_revenue: number;
}

interface RevenueChartProps {
  data: RevenueData[];
  title: string;
  height?: number;
}

export const RevenueChart: React.FC<RevenueChartProps> = ({ 
  data, 
  title, 
  height = 300 
}) => {
  return (
    <div className="w-full">
      <h3 className="text-lg font-semibold mb-4">{title}</h3>
      <ResponsiveContainer width="100%" height={height}>
        <BarChart data={data} margin={{ top: 20, right: 30, left: 20, bottom: 5 }}>
          <CartesianGrid strokeDasharray="3 3" />
          <XAxis 
            dataKey="name" 
            angle={-45}
            textAnchor="end"
            height={80}
            interval={0}
          />
          <YAxis />
          <Tooltip 
            formatter={(value, name) => [
              name === 'estimated_revenue' ? `₹${value.toLocaleString()}` : value,
              name === 'estimated_revenue' ? 'Revenue' : 'Completed Leads'
            ]}
          />
          <Legend />
          <Bar dataKey="completed_leads" fill="#3B82F6" name="Completed Leads" />
          <Bar dataKey="estimated_revenue" fill="#10B981" name="Revenue (₹)" />
        </BarChart>
      </ResponsiveContainer>
    </div>
  );
};

// Performance Chart Component
interface PerformanceData {
  user_name: string;
  leads_created: number;
  leads_completed: number;
  conversion_rate: number;
  target_achievement: number;
}

interface PerformanceChartProps {
  data: PerformanceData[];
  height?: number;
}

export const PerformanceChart: React.FC<PerformanceChartProps> = ({ 
  data, 
  height = 400 
}) => {
  return (
    <div className="w-full">
      <h3 className="text-lg font-semibold mb-4">User Performance</h3>
      <ResponsiveContainer width="100%" height={height}>
        <BarChart data={data} margin={{ top: 20, right: 30, left: 20, bottom: 5 }}>
          <CartesianGrid strokeDasharray="3 3" />
          <XAxis 
            dataKey="user_name" 
            angle={-45}
            textAnchor="end"
            height={80}
            interval={0}
          />
          <YAxis />
          <Tooltip />
          <Legend />
          <Bar dataKey="leads_created" fill="#8B5CF6" name="Leads Created" />
          <Bar dataKey="leads_completed" fill="#10B981" name="Leads Completed" />
          <Bar dataKey="conversion_rate" fill="#F59E0B" name="Conversion Rate (%)" />
        </BarChart>
      </ResponsiveContainer>
    </div>
  );
};

// Shift Status Pie Chart
interface ShiftStatusData {
  name: string;
  value: number;
}

interface ShiftStatusChartProps {
  totalShifts: number;
  activeShifts: number;
  completedShifts: number;
  height?: number;
}

export const ShiftStatusChart: React.FC<ShiftStatusChartProps> = ({ 
  totalShifts,
  activeShifts,
  completedShifts,
  height = 300 
}) => {
  const data: ShiftStatusData[] = [
    { name: 'Active Shifts', value: activeShifts },
    { name: 'Completed Shifts', value: completedShifts },
  ];

  const COLORS = ['#10B981', '#3B82F6'];

  return (
    <div className="w-full">
      <h3 className="text-lg font-semibold mb-4">Shift Status</h3>
      <ResponsiveContainer width="100%" height={height}>
        <PieChart>
          <Pie
            data={data}
            cx="50%"
            cy="50%"
            labelLine={false}
            label={({ name, value, percent }) => `${name}: ${value} (${(percent * 100).toFixed(0)}%)`}
            outerRadius={80}
            fill="#8884d8"
            dataKey="value"
          >
            {data.map((entry, index) => (
              <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
            ))}
          </Pie>
          <Tooltip />
        </PieChart>
      </ResponsiveContainer>
    </div>
  );
};

// Daily Performance Line Chart
interface DailyPerformanceData {
  date: string;
  leads_created: number;
  leads_completed: number;
  meetings_conducted: number;
}

interface DailyPerformanceChartProps {
  data: DailyPerformanceData[];
  height?: number;
}

export const DailyPerformanceChart: React.FC<DailyPerformanceChartProps> = ({ 
  data, 
  height = 300 
}) => {
  return (
    <div className="w-full">
      <h3 className="text-lg font-semibold mb-4">Daily Performance Trend</h3>
      <ResponsiveContainer width="100%" height={height}>
        <LineChart data={data} margin={{ top: 20, right: 30, left: 20, bottom: 5 }}>
          <CartesianGrid strokeDasharray="3 3" />
          <XAxis 
            dataKey="date" 
            tickFormatter={(value) => new Date(value).toLocaleDateString()}
          />
          <YAxis />
          <Tooltip 
            labelFormatter={(value) => new Date(value).toLocaleDateString()}
          />
          <Legend />
          <Line 
            type="monotone" 
            dataKey="leads_created" 
            stroke="#8B5CF6" 
            strokeWidth={2}
            name="Leads Created"
          />
          <Line 
            type="monotone" 
            dataKey="leads_completed" 
            stroke="#10B981" 
            strokeWidth={2}
            name="Leads Completed"
          />
          <Line 
            type="monotone" 
            dataKey="meetings_conducted" 
            stroke="#F59E0B" 
            strokeWidth={2}
            name="Meetings"
          />
        </LineChart>
      </ResponsiveContainer>
    </div>
  );
};

// Revenue Trend Area Chart
interface RevenueTrendData {
  date: string;
  estimated_revenue: number;
  completed_leads: number;
}

interface RevenueTrendChartProps {
  data: RevenueTrendData[];
  height?: number;
}

export const RevenueTrendChart: React.FC<RevenueTrendChartProps> = ({ 
  data, 
  height = 300 
}) => {
  return (
    <div className="w-full">
      <h3 className="text-lg font-semibold mb-4">Revenue Trend</h3>
      <ResponsiveContainer width="100%" height={height}>
        <AreaChart data={data} margin={{ top: 20, right: 30, left: 20, bottom: 5 }}>
          <CartesianGrid strokeDasharray="3 3" />
          <XAxis 
            dataKey="date" 
            tickFormatter={(value) => new Date(value).toLocaleDateString()}
          />
          <YAxis 
            tickFormatter={(value) => `₹${(value / 1000).toFixed(0)}K`}
          />
          <Tooltip 
            labelFormatter={(value) => new Date(value).toLocaleDateString()}
            formatter={(value: any) => [`₹${value.toLocaleString()}`, 'Revenue']}
          />
          <Area 
            type="monotone" 
            dataKey="estimated_revenue" 
            stroke="#10B981" 
            fill="#10B981" 
            fillOpacity={0.3}
          />
        </AreaChart>
      </ResponsiveContainer>
    </div>
  );
};

