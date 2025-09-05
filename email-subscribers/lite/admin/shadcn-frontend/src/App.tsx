import React from 'react';
import { createHashRouter, RouterProvider } from 'react-router-dom';
import Dashboard from './components/Dashboard';
import WorkflowList from './components/WorkflowList';
import { WorkflowBuilder } from './components/WorkflowBuilder';
import FormList from './components/FormList';
import Pricing from './components/Pricing';
import './index.css';

const router = createHashRouter([
  {
    path: "/",
    element: <Dashboard />,
  },
  {
    path: "/dashboard",
    element: <Dashboard />,
  },
  {
    path: "/workflows",
    element: <WorkflowList />,
  },
  {
    path: "/workflow-builder",
    element: <WorkflowBuilder />,
  },
  {
    path: "/forms",
    element: <FormList />,
  },
  {
    path: "/pricing",
    element: <Pricing />,
  },
]);

const App: React.FC = () => {
  return (
    <div className="min-h-screen">
      <RouterProvider router={router} />
    </div>
  );
};

export default App;
