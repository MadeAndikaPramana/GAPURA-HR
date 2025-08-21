// resources/js/Components/ApplicationLogo.jsx

export default function ApplicationLogo(props) {
    return (
        <div className="flex items-center justify-center" {...props}>
            <div className="w-10 h-10 bg-green-600 rounded-lg flex items-center justify-center">
                <span className="text-white font-bold text-xl">G</span>
            </div>
            <div className="ml-3">
                <div className="text-lg font-bold text-gray-900">GAPURA</div>
                <div className="text-xs text-gray-600">Training System</div>
            </div>
        </div>
    );
}
