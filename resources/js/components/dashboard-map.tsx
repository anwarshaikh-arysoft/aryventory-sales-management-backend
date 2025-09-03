import React, { useEffect, useState } from 'react';
import { MapContainer, TileLayer, Marker, Popup } from 'react-leaflet';
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';

// Fix for default markers in React Leaflet
delete (L.Icon.Default.prototype as any)._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png',
  iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
});

interface ShiftLocation {
  id: number;
  user_name: string;
  designation: string;
  group: string;
  shift_date: string;
  shift_start: string;
  latitude: number;
  longitude: number;
  is_active: boolean;
}

interface DashboardMapProps {
  locations: ShiftLocation[];
  height?: string;
}

const DashboardMap: React.FC<DashboardMapProps> = ({ locations, height = '400px' }) => {
  const [map, setMap] = useState<L.Map | null>(null);

  // Default center (India coordinates)
  const defaultCenter: [number, number] = [20.5937, 78.9629];

  // Custom icons for active and inactive shifts
  const activeShiftIcon = new L.Icon({
    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41]
  });

  const inactiveShiftIcon = new L.Icon({
    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41]
  });

  // Calculate bounds to fit all markers
  useEffect(() => {
    if (map && locations.length > 0) {
      const group = new L.featureGroup(
        locations.map(location => 
          L.marker([location.latitude, location.longitude])
        )
      );
      map.fitBounds(group.getBounds().pad(0.1));
    }
  }, [map, locations]);

  if (locations.length === 0) {
    return (
      <div 
        className="flex items-center justify-center bg-gray-100 rounded-lg border"
        style={{ height }}
      >
        <p className="text-gray-500">No shift locations to display</p>
      </div>
    );
  }

  return (
    <div style={{ height }} className="rounded-lg overflow-hidden border">
      <MapContainer
        center={defaultCenter}
        zoom={5}
        style={{ height: '100%', width: '100%' }}
        ref={setMap}
      >
        <TileLayer
          attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
          url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
        />
        {locations.map((location) => (
          <Marker
            key={location.id}
            position={[location.latitude, location.longitude]}
            icon={location.is_active ? activeShiftIcon : inactiveShiftIcon}
          >
            <Popup>
              <div className="p-2">
                <h3 className="font-semibold text-lg">{location.user_name}</h3>
                <p className="text-sm text-gray-600">{location.designation}</p>
                <p className="text-sm text-gray-600">Region: {location.group}</p>
                <p className="text-sm text-gray-600">
                  Date: {new Date(location.shift_date).toLocaleDateString()}
                </p>
                <p className="text-sm text-gray-600">
                  Start Time: {new Date(location.shift_start).toLocaleTimeString()}
                </p>
                <div className="mt-2">
                  <span 
                    className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${
                      location.is_active 
                        ? 'bg-green-100 text-green-800' 
                        : 'bg-red-100 text-red-800'
                    }`}
                  >
                    {location.is_active ? 'Active Shift' : 'Shift Ended'}
                  </span>
                </div>
              </div>
            </Popup>
          </Marker>
        ))}
      </MapContainer>
    </div>
  );
};

export default DashboardMap;
