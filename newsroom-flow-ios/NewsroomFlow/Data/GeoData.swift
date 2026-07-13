import Foundation

/// Country + US-state option lists for the local-area form. Mirrors the web
/// LocationQuery constants (the server validates authoritatively).
enum GeoData {
    // US pinned first; the rest alphabetical by name.
    static let countries: [(code: String, name: String)] = [
        ("US", "United States"),
        ("AU", "Australia"),
        ("BR", "Brazil"),
        ("CA", "Canada"),
        ("FR", "France"),
        ("DE", "Germany"),
        ("IN", "India"),
        ("IE", "Ireland"),
        ("IT", "Italy"),
        ("JP", "Japan"),
        ("KE", "Kenya"),
        ("MX", "Mexico"),
        ("NL", "Netherlands"),
        ("NZ", "New Zealand"),
        ("NG", "Nigeria"),
        ("PH", "Philippines"),
        ("SG", "Singapore"),
        ("ZA", "South Africa"),
        ("ES", "Spain"),
        ("GB", "United Kingdom"),
    ]

    static let usStates: [(code: String, name: String)] = [
        ("AL", "Alabama"), ("AK", "Alaska"), ("AZ", "Arizona"), ("AR", "Arkansas"),
        ("CA", "California"), ("CO", "Colorado"), ("CT", "Connecticut"), ("DE", "Delaware"),
        ("DC", "District of Columbia"), ("FL", "Florida"), ("GA", "Georgia"), ("HI", "Hawaii"),
        ("ID", "Idaho"), ("IL", "Illinois"), ("IN", "Indiana"), ("IA", "Iowa"),
        ("KS", "Kansas"), ("KY", "Kentucky"), ("LA", "Louisiana"), ("ME", "Maine"),
        ("MD", "Maryland"), ("MA", "Massachusetts"), ("MI", "Michigan"), ("MN", "Minnesota"),
        ("MS", "Mississippi"), ("MO", "Missouri"), ("MT", "Montana"), ("NE", "Nebraska"),
        ("NV", "Nevada"), ("NH", "New Hampshire"), ("NJ", "New Jersey"), ("NM", "New Mexico"),
        ("NY", "New York"), ("NC", "North Carolina"), ("ND", "North Dakota"), ("OH", "Ohio"),
        ("OK", "Oklahoma"), ("OR", "Oregon"), ("PA", "Pennsylvania"), ("RI", "Rhode Island"),
        ("SC", "South Carolina"), ("SD", "South Dakota"), ("TN", "Tennessee"), ("TX", "Texas"),
        ("UT", "Utah"), ("VT", "Vermont"), ("VA", "Virginia"), ("WA", "Washington"),
        ("WV", "West Virginia"), ("WI", "Wisconsin"), ("WY", "Wyoming"),
    ]
}
