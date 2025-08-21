import { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    
    return (
        <div className='bg-primary rounded'>
            <img className='w-100'
                src="/assets/aryventory-logo.svg"
                alt="Aryventory Logo"                
            />            
        </div>
    );
}


