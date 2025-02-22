import React from 'react';

interface EnvelopeCardProps {
    children: React.ReactNode;
}

const EnvelopeCard: React.FC<EnvelopeCardProps> = ({ children }) => {
    return (
        <div className="flex items-center justify-between mb-4">
            <div className="flex-grow">
                <div className="flex items-center">
                    {children}
                </div>
            </div>
        </div>
    );
};

export default EnvelopeCard;
