import React from 'react';

interface EnvelopeCardProps {
    children: React.ReactNode;
}

const EnvelopeCard: React.FC<EnvelopeCardProps> = ({ children }) => {
    return (
        <div className="flex items-center justify-between mb-4">

            {children}

        </div>
    );
};

export default EnvelopeCard;
