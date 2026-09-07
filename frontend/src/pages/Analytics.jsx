import { useEffect, useState } from 'react'
import './Analytics.css'

const API_BASE_URL =
  'http://localhost/support-desk/backend/web/v1'


function Analytics({ token, user }) {
  const [analytics, setAnalytics] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    const loadAnalytics = async () => {
      try {
        setLoading(true)
        setError('')

        const response = await fetch(
          `${API_BASE_URL}/dashboard`,
          {
            headers: {
              Accept: 'application/json',
              Authorization: `Bearer ${token}`,
            },
          }
        )

        const data = await response.json()

        if (!response.ok) {
          throw new Error(
            data.message || 'Failed to load analytics'
          )
        }

        setAnalytics(data)
      } catch (err) {
        setError(
          err.message || 'Failed to load analytics'
        )
      } finally {
        setLoading(false)
      }
    }

    if (token) {
      loadAnalytics()
    }
  }, [token])

  if (loading) {
    return (
      <section className="content">
        <div className="analytics-loading">
          Loading analytics...
        </div>
      </section>
    )
  }

  if (error) {
    return (
      <section className="content">
        <div className="analytics-error">
          {error}
        </div>
      </section>
    )
  }

    const totalTickets = analytics?.tickets?.total ?? 0

    const getPercentage = (value) => {
    if (totalTickets === 0) {
        return 0
    }

    return Math.round((value / totalTickets) * 100)
    }


  return (
    <section className="content">

      {/* HEADER */}

      <div className="analytics-page">

        <div className="analytics-header">

            <div>
                <div className="section-label">
                REPORTING
                </div>

                <h1>
                Analytics
                </h1>

                <p>
                Overview of support desk performance.
                </p>
            </div>


            <button
                className="refresh-button"
                onClick={() => window.location.reload()}
            >
                ↻ Refresh
            </button>

        </div>


        {/* SUMMARY CARDS */}

        <div className="analytics-cards">

          <div className="analytics-card">
            <span>Total Tickets</span>
            <strong>
              {analytics?.tickets?.total ?? 0}
            </strong>
          </div>


          <div className="analytics-card">
            <span>Open Tickets</span>
            <strong>
              {analytics?.tickets?.open ?? 0}
            </strong>
          </div>


          <div className="analytics-card">
            <span>Pending Tickets</span>
            <strong>
              {analytics?.tickets?.pending ?? 0}
            </strong>
          </div>


          <div className="analytics-card">
            <span>Resolved Tickets</span>
            <strong>
              {analytics?.tickets?.resolved ?? 0}
            </strong>
          </div>


          <div className="analytics-card">
            <span>Average Resolution Time</span>
            <strong>
                {analytics?.average_resolution_seconds
                ? `${Math.round(
                    analytics.average_resolution_seconds / 3600
                    )} hrs`
                : '0 hrs'}
            </strong>
          </div>

        </div>


        {user?.role === 'agent' && (
            <div className="analytics-section">
                <h2>My Staff Metrics</h2>

                <div className="analytics-cards">
                <div className="analytics-card">
                    <span>My Assigned</span>
                    <strong>
                    {analytics?.staff_metrics?.my_assigned ?? 0}
                    </strong>
                </div>

                <div className="analytics-card">
                    <span>My Open Assigned</span>
                    <strong>
                    {analytics?.staff_metrics?.my_open_assigned ?? 0}
                    </strong>
                </div>

                <div className="analytics-card">
                    <span>My Overdue</span>
                    <strong>
                    {analytics?.staff_metrics?.my_overdue_assigned ?? 0}
                    </strong>
                </div>

                <div className="analytics-card">
                    <span>My Critical</span>
                    <strong>
                    {analytics?.staff_metrics?.my_critical_assigned ?? 0}
                    </strong>
                </div>

                <div className="analytics-card">
                    <span>Recently Updated</span>
                    <strong>
                    {analytics?.staff_metrics?.my_recently_updated ?? 0}
                    </strong>
                </div>
                </div>
            </div>
        )}


        {/* ANALYTICS SECTIONS */}

        <div className="analytics-grid">

            {/* STATUS */}

            <div className="analytics-section">

                <h2>
                    Ticket Status
                </h2>

                <div className="analytics-bar-list">

                    <div className="analytics-bar-item">

                        <div className="analytics-bar-label">
                            <span>Open</span>
                            <strong>
                            {analytics?.tickets?.open ?? 0}
                            {' '}
                            ({getPercentage(analytics?.tickets?.open ?? 0)}%)
                            </strong>
                        </div>

                        <div className="analytics-bar-track">
                            <div
                            className="analytics-bar-fill"
                            style={{
                                width: `${getPercentage(
                                analytics?.tickets?.open ?? 0
                                )}%`,
                            }}
                            />
                        </div>

                    </div>


                    <div className="analytics-bar-item">

                        <div className="analytics-bar-label">
                            <span>Pending</span>
                            <strong>
                            {analytics?.tickets?.pending ?? 0}
                            {' '}
                            ({getPercentage(analytics?.tickets?.pending ?? 0)}%)
                            </strong>
                        </div>

                        <div className="analytics-bar-track">
                            <div
                            className="analytics-bar-fill"
                            style={{
                                width: `${getPercentage(
                                analytics?.tickets?.pending ?? 0
                                )}%`,
                            }}
                            />
                        </div>

                    </div>


                    <div className="analytics-bar-item">

                        <div className="analytics-bar-label">
                            <span>Resolved</span>
                            <strong>
                            {analytics?.tickets?.resolved ?? 0}
                            {' '}
                            ({getPercentage(analytics?.tickets?.resolved ?? 0)}%)
                            </strong>
                        </div>

                        <div className="analytics-bar-track">
                            <div
                            className="analytics-bar-fill"
                            style={{
                                width: `${getPercentage(
                                analytics?.tickets?.resolved ?? 0
                                )}%`,
                            }}
                            />
                        </div>

                    </div>

                </div>

            </div>


          {/* PRIORITY */}

        <div className="analytics-section">

            <h2>
                Priority Distribution
            </h2>

            <div className="analytics-bar-list">

                <div className="analytics-bar-item">

                    <div className="analytics-bar-label">
                        <span>Low</span>
                        <strong>
                        {analytics?.priority?.low ?? 0}
                        {' '}
                        ({getPercentage(analytics?.priority?.low ?? 0)}%)
                        </strong>
                    </div>

                    <div className="analytics-bar-track">
                        <div
                            className="analytics-bar-fill"
                            style={{
                                width: `${getPercentage(
                                analytics?.priority?.low ?? 0
                                )}%`,
                            }}
                            />
                    </div>

                </div>


                <div className="analytics-bar-item">

                <div className="analytics-bar-label">
                    <span>Medium</span>
                    <strong>
                    {analytics?.priority?.medium ?? 0}
                    {' '}
                    ({getPercentage(analytics?.priority?.medium ?? 0)}%)
                    </strong>
                </div>

                <div className="analytics-bar-track">
                    <div
                    className="analytics-bar-fill"
                    style={{
                        width: `${getPercentage(
                        analytics?.priority?.medium ?? 0
                        )}%`,
                    }}
                    />
                </div>

                </div>


                <div className="analytics-bar-item">

                <div className="analytics-bar-label">
                    <span>High</span>
                    <strong>
                    {analytics?.priority?.high ?? 0}
                    {' '}
                    ({getPercentage(analytics?.priority?.high ?? 0)}%)
                    </strong>
                </div>

                <div className="analytics-bar-track">
                    <div
                    className="analytics-bar-fill"
                    style={{
                        width: `${getPercentage(
                        analytics?.priority?.high ?? 0
                        )}%`,
                    }}
                    />
                </div>

                </div>


                <div className="analytics-bar-item">

                <div className="analytics-bar-label">
                    <span>Critical</span>
                    <strong>
                    {analytics?.priority?.critical ?? 0}
                    {' '}
                    ({getPercentage(analytics?.priority?.critical ?? 0)}%)
                    </strong>
                </div>

                <div className="analytics-bar-track">
                    <div
                    className="analytics-bar-fill"
                    style={{
                        width: `${getPercentage(
                        analytics?.priority?.critical ?? 0
                        )}%`,
                    }}
                    />
                </div>

                </div>

            </div>

        </div>


        
                  {/* CATEGORY */}

        <div className="analytics-section">

            <h2>
                Category Distribution
            </h2>

            <div className="analytics-bar-list">

                {Object.entries(
                    analytics?.category ?? {}
                ).map(([category, count]) => (

                    <div
                        className="analytics-bar-item"
                        key={category}
                    >

                        <div className="analytics-bar-label">

                            <span>
                                {category}
                            </span>

                            <strong>
                                {count}
                                {' '}
                                ({getPercentage(count)}%)
                            </strong>

                        </div>

                        <div className="analytics-bar-track">

                            <div
                                className="analytics-bar-fill"
                                style={{
                                    width: `${getPercentage(count)}%`,
                                }}
                            />

                        </div>

                    </div>

                ))}

                {Object.keys(
                    analytics?.category ?? {}
                ).length === 0 && (

                    <div>
                        <span>No category data</span>
                        <strong>0</strong>
                    </div>

                )}

            </div>

        </div>



          {/* SLA */}

        <div className="analytics-section">

        <h2>
            SLA Overview
        </h2>

        <div className="analytics-sla-summary">

            <div className="analytics-sla-box">
            <span>SLA Breached</span>

            <strong>
                {analytics?.sla?.breached ?? 0}
            </strong>
            </div>


            <div className="analytics-sla-box">
            <span>At Risk</span>

            <strong>
                {analytics?.sla?.at_risk ?? 0}
            </strong>
            </div>

        </div>


        <div className="analytics-sla-health">

            <div className="analytics-sla-health-label">

            <span>
                SLA Health
            </span>

            <strong>
                {
                totalTickets === 0
                    ? 100
                    : Math.max(
                        0,
                        Math.round(
                        (
                            (
                            totalTickets -
                            (analytics?.sla?.breached ?? 0)
                            ) /
                            totalTickets
                        ) * 100
                        )
                    )
                }%
            </strong>

            </div>


            <div className="analytics-sla-track">

            <div
                className="analytics-sla-fill"
                style={{
                width: `${
                    totalTickets === 0
                    ? 100
                    : Math.max(
                        0,
                        Math.round(
                            (
                            (
                                totalTickets -
                                (analytics?.sla?.breached ?? 0)
                            ) /
                            totalTickets
                            ) * 100
                        )
                        )
                }%`,
                }}
            />

            </div>

        </div>

        </div>


        {/* ESCALATIONS */}

        <div className="analytics-section">

        <h2>
            Escalation Overview
        </h2>

        <div className="analytics-list">

            <div>
            <span>Tickets Requiring Attention</span>

            <strong>
                {
                (analytics?.sla?.breached ?? 0) +
                (analytics?.sla?.at_risk ?? 0)
                }
            </strong>

            </div>

        </div>

        </div>
                  
            
            <div className="analytics-section">
                <h2>Agent Workload</h2>

                <div className="analytics-list">
                {Object.values(
                    analytics?.agent_workload ?? {}
                ).map((agent) => (
                    <div key={agent.agent_id}>
                    <span>{agent.agent_name}</span>

                    <strong>
                        {agent.ticket_count}
                    </strong>
                    </div>
                ))}

                {Object.keys(
                    analytics?.agent_workload ?? {}
                ).length === 0 && (
                    <div>
                    <span>No assigned tickets</span>
                    <strong>0</strong>
                    </div>
                )}
              </div>
            </div>


        </div>

      </div>

    </section>
  )
}

export default Analytics