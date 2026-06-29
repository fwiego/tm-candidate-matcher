import React from 'react';
import { createRoot } from 'react-dom/client';
import './bootstrap';

function App() {
    return (
        <div style={{ textAlign: 'center', padding: '50px' }}>
            <h1 style={{ color: '#6366f1' }}>🚀 Laravel + React работает!</h1>
            <p>Если вы это видите - React успешно загружен!</p>
            <button 
                onClick={() => alert('Привет!')}
                style={{
                    background: '#6366f1',
                    color: 'white',
                    border: 'none',
                    padding: '10px 20px',
                    borderRadius: '8px',
                    cursor: 'pointer',
                    fontSize: '16px',
                    marginTop: '20px'
                }}
            >
                Нажми меня
            </button>
        </div>
    );
}

const root = createRoot(document.getElementById('app'));
root.render(<App />);