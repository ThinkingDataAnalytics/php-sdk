### v3.1.3
**Date:** 2026/09/22

**Notes:**

* Keep file-consumer buffers retryable when log-file resolution or filesystem probes fail
* Recover incomplete pending-file state instead of passing null paths to filesystem APIs
* Fail fast when the configured log directory cannot be created
* Report buffered records as unwritten instead of dropped when close-time flush fails

### v3.1.2
**Date:** 2026/09/15

**Notes:**

* Improve the stability of the SDK

### v3.1.1
**Date:** 2024/07/24

**Notes:**

* More permissions are supported

### v3.1.0
**Date:** 2024/07/19

**Notes:**

* Supports user-defined permissions

### v3.0.0
**Date:** 2023/10/08

**Notes:**

* Enabling new apis

### v2.2.2
**Date:** 2023/06/29

**Notes:**

* bugfix: close file handler when file rotate

### v2.2.1
**Date:** 2023/04/11

**Notes:**

* Close file handler when SDK close

### v2.2.0
**Date:** 2023/04/10

**Notes:**

* Add TE namespace
* Add buffer

### v2.1.1
**Date:** 2022/11/10

**Notes:**

* Support debug mode in TE

### v2.1.0
**Date:** 2022/10/25

**Notes:**

* Support log system

### v2.0.0
**Date:** 2022/05/09

**Notes:**

* Support dynamic common properties
* Add track_first api
* Add user_uniq_append api
